<?php
/**
 * Session Bridge - PHPネイティブセッション読み取り版
 * 
 * EC-CUBEのSymfonyカーネルをブートせず、PHPネイティブセッションで直接
 * セッションファイルを読み取り、ログインユーザーのメールアドレスを取得する。
 * 
 * URL: https://tw.kyogokupro.com/session_bridge.php?return_url=...
 * 
 * フロー:
 * 1. SalesDash /tw/cart → ログイン未識別
 * 2. → tw.kyogokupro.com/session_bridge.php?return_url=https://salesdash.buzzdrop.co.jp/tw/cart
 * 3. PHPネイティブセッション読み取り → _security_customer からメール取得
 * 4. ログイン中 → return_url?email=xxx&ts=xxx&sig=xxx にリダイレクト
 *    未ログイン → return_url?no_session=1 にリダイレクト
 * 
 * セキュリティ:
 * - return_urlはsalesdash.buzzdrop.co.jpドメインのみ許可
 * - HMAC署名でリダイレクトの正当性を検証
 * - タイムスタンプで5分以内のリダイレクトのみ有効
 * 
 * セッション構造:
 * - Cookie名: eccube (ECCUBE_COOKIE_NAME)
 * - 保存先: var/sessions/prod/ (Symfonyのsession.handler.native_file)
 * - セキュリティトークン: $_SESSION['_sf2_attributes']['_security_customer']
 *   → シリアライズされたUsernamePasswordToken/PreAuthenticatedTokenオブジェクト
 * 
 * 作成日: 2026-04-17
 * 更新日: 2026-04-17 (PHPネイティブセッション方式に変更)
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// デバッグモード（本番では false にする）
define('DEBUG_MODE', false);

// HMAC署名用の秘密鍵
define('BRIDGE_SECRET', 'kg_tw_session_bridge_2026');

// EC-CUBEのセッション設定
define('ECCUBE_SESSION_NAME', 'eccube');
define('ECCUBE_SESSION_SAVE_PATH', __DIR__ . '/var/sessions/prod');

// 許可するリダイレクト先ドメイン
$allowedDomains = ['salesdash.buzzdrop.co.jp'];

// デバッグログ
$debugLog = [];
function debugLog($msg) {
    global $debugLog;
    $debugLog[] = $msg;
}

// return_urlの取得とバリデーション
$returnUrl = isset($_GET['return_url']) ? $_GET['return_url'] : '';

if (empty($returnUrl)) {
    header('Location: /');
    exit;
}

$parsed = parse_url($returnUrl);
if (!$parsed || !isset($parsed['host']) || !in_array($parsed['host'], $allowedDomains, true)) {
    header('Location: /');
    exit;
}

$email = null;

try {
    // --- 方式1: PHPネイティブセッション読み取り ---
    
    // EC-CUBEのセッションCookieが送信されているか確認
    $sessionId = isset($_COOKIE[ECCUBE_SESSION_NAME]) ? $_COOKIE[ECCUBE_SESSION_NAME] : null;
    debugLog('Session cookie name: ' . ECCUBE_SESSION_NAME);
    debugLog('Session ID from cookie: ' . ($sessionId ? substr($sessionId, 0, 8) . '...' : 'NOT FOUND'));
    debugLog('All cookies: ' . implode(', ', array_keys($_COOKIE)));
    
    if ($sessionId) {
        // セッション保存先を設定
        $savePath = ECCUBE_SESSION_SAVE_PATH;
        debugLog('Session save path: ' . $savePath);
        debugLog('Save path exists: ' . (is_dir($savePath) ? 'YES' : 'NO'));
        
        if (is_dir($savePath)) {
            // セッションファイルを直接読み取る（session_start()を使わない方式）
            // Symfonyはセッションファイル名を sess_{session_id} 形式で保存する
            $sessionFile = $savePath . '/sess_' . $sessionId;
            debugLog('Session file path: ' . $sessionFile);
            debugLog('Session file exists: ' . (file_exists($sessionFile) ? 'YES' : 'NO'));
            
            if (file_exists($sessionFile)) {
                $sessionData = file_get_contents($sessionFile);
                debugLog('Session file size: ' . strlen($sessionData) . ' bytes');
                
                // セッションデータをデコード
                $email = extractEmailFromSessionData($sessionData);
                debugLog('Extracted email: ' . ($email ?: 'NOT FOUND'));
            } else {
                debugLog('Session file not found, trying session_start approach');
                
                // セッションファイルが見つからない場合、session_start()で試す
                $email = trySessionStartApproach($savePath, $sessionId);
            }
        } else {
            debugLog('Save path does not exist, trying default PHP session path');
            
            // var/sessions/prod が存在しない場合、PHPデフォルトのセッションパスを試す
            $email = trySessionStartApproach(null, $sessionId);
        }
    } else {
        debugLog('No session cookie found');
    }
    
} catch (\Exception $e) {
    debugLog('Exception: ' . $e->getMessage());
}

// デバッグモード時はJSONでデバッグ情報を返す
if (DEBUG_MODE && isset($_GET['debug'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'email' => $email,
        'debug' => $debugLog,
        'cookies' => array_keys($_COOKIE),
        'session_name' => ECCUBE_SESSION_NAME,
        'save_path' => ECCUBE_SESSION_SAVE_PATH,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$sep = (strpos($returnUrl, '?') !== false) ? '&' : '?';

if ($email) {
    // ログイン中 → emailとHMAC署名付きでリダイレクト
    $timestamp = time();
    $signature = hash_hmac('sha256', $email . ':' . $timestamp, BRIDGE_SECRET);
    
    $redirectUrl = $returnUrl . $sep . http_build_query([
        'email' => $email,
        'ts' => $timestamp,
        'sig' => $signature,
    ]);
    
    header('Location: ' . $redirectUrl);
    exit;
}

// 未ログイン → no_session=1 でリダイレクトバック
header('Location: ' . $returnUrl . $sep . 'no_session=1');
exit;

// ========== ヘルパー関数 ==========

/**
 * セッションデータからメールアドレスを抽出する
 * 
 * Symfonyのセッションデータ形式:
 * _sf2_attributes|a:N:{s:18:"_security_customer";s:XXX:"serialized_token";}
 * 
 * シリアライズされたトークン内のCustomerオブジェクトからemailを抽出する
 */
function extractEmailFromSessionData($rawData) {
    debugLog('--- Parsing session data ---');
    
    // PHPのセッションデータをデコード
    $sessionArray = sessionDecode($rawData);
    
    if (!$sessionArray) {
        debugLog('Failed to decode session data');
        // フォールバック: 正規表現で直接メールアドレスを抽出
        return extractEmailByRegex($rawData);
    }
    
    debugLog('Session keys: ' . implode(', ', array_keys($sessionArray)));
    
    // Symfonyのセッション構造: _sf2_attributes 内に _security_customer がある
    $securityToken = null;
    
    if (isset($sessionArray['_sf2_attributes']['_security_customer'])) {
        $securityToken = $sessionArray['_sf2_attributes']['_security_customer'];
        debugLog('Found _security_customer in _sf2_attributes');
    } elseif (isset($sessionArray['_security_customer'])) {
        $securityToken = $sessionArray['_security_customer'];
        debugLog('Found _security_customer at top level');
    }
    
    if ($securityToken) {
        debugLog('Security token length: ' . strlen($securityToken));
        // トークンからメールアドレスを抽出（正規表現）
        return extractEmailFromToken($securityToken);
    }
    
    debugLog('No _security_customer found, trying regex on raw data');
    return extractEmailByRegex($rawData);
}

/**
 * PHPセッションデータをデコードする
 * session_decode()はグローバル$_SESSIONに書き込むため、カスタム実装
 */
function sessionDecode($data) {
    $result = [];
    $offset = 0;
    $length = strlen($data);
    
    while ($offset < $length) {
        // キー名を取得（|で区切られている）
        $pipePos = strpos($data, '|', $offset);
        if ($pipePos === false) {
            break;
        }
        
        $key = substr($data, $offset, $pipePos - $offset);
        $offset = $pipePos + 1;
        
        // 値をunserializeする
        $valueData = substr($data, $offset);
        
        // unserializeで値を取得し、消費したバイト数を計算
        $value = unserializeWithLength($valueData, $consumed);
        
        if ($value !== false || $valueData === 'b:0;') {
            $result[$key] = $value;
            $offset += $consumed;
        } else {
            // unserializeに失敗した場合、次の|を探す
            break;
        }
    }
    
    return $result;
}

/**
 * unserializeして消費したバイト数を返す
 */
function unserializeWithLength($data, &$consumed) {
    // エラーハンドリング付きunserialize
    $prevHandler = set_error_handler(function() { return true; });
    
    // SymfonyのセキュリティトークンにはEC-CUBEのクラスが必要
    // autoloaderが読み込まれていない場合、unserializeは失敗する
    // その場合は正規表現フォールバックを使用
    
    $result = @unserialize($data);
    set_error_handler($prevHandler);
    
    if ($result !== false) {
        $consumed = strlen(serialize($result));
        return $result;
    }
    
    // シリアライズされた文字列の長さを手動で計算
    if (preg_match('/^([a-z]):/', $data, $m)) {
        switch ($m[1]) {
            case 's': // string
                if (preg_match('/^s:(\d+):"/', $data, $sm)) {
                    $strLen = (int)$sm[1];
                    $consumed = strlen($sm[0]) + $strLen + 2; // s:N:"..." + ";
                    return substr($data, strlen($sm[0]), $strLen);
                }
                break;
            case 'i': // integer
                if (preg_match('/^i:(-?\d+);/', $data, $im)) {
                    $consumed = strlen($im[0]);
                    return (int)$im[1];
                }
                break;
            case 'b': // boolean
                if (preg_match('/^b:([01]);/', $data, $bm)) {
                    $consumed = strlen($bm[0]);
                    return (bool)$bm[1];
                }
                break;
            case 'N': // null
                $consumed = 2; // N;
                return null;
            case 'a': // array
                // 配列の場合、シリアライズされた全体を見つける必要がある
                $consumed = findSerializedEnd($data);
                if ($consumed > 0) {
                    $serialized = substr($data, 0, $consumed);
                    $val = @unserialize($serialized);
                    if ($val !== false || $serialized === 'a:0:{}') {
                        return $val;
                    }
                }
                break;
        }
    }
    
    $consumed = 0;
    return false;
}

/**
 * シリアライズされたデータの終端位置を見つける
 */
function findSerializedEnd($data) {
    $depth = 0;
    $i = 0;
    $len = strlen($data);
    $inString = false;
    $stringRemaining = 0;
    
    while ($i < $len) {
        if ($stringRemaining > 0) {
            $i += $stringRemaining;
            $stringRemaining = 0;
            // ";を期待
            if ($i < $len && $data[$i] === '"') {
                $i++; // "
                if ($i < $len && $data[$i] === ';') {
                    $i++; // ;
                }
            }
            continue;
        }
        
        $char = $data[$i];
        
        if ($char === '{') {
            $depth++;
            $i++;
        } elseif ($char === '}') {
            $depth--;
            $i++;
            if ($depth === 0) {
                return $i;
            }
        } elseif ($char === 's' && $i + 1 < $len && $data[$i + 1] === ':') {
            // 文字列: s:N:"...";
            if (preg_match('/^s:(\d+):"/', substr($data, $i), $m)) {
                $strLen = (int)$m[1];
                $i += strlen($m[0]) + $strLen + 2; // skip s:N:" + content + ";
            } else {
                $i++;
            }
        } else {
            $i++;
        }
    }
    
    return 0;
}

/**
 * シリアライズされたトークンからメールアドレスを抽出する
 */
function extractEmailFromToken($tokenData) {
    debugLog('--- Extracting email from token ---');
    
    // 方式1: autoloaderが利用可能な場合、unserializeを試みる
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        debugLog('Autoloader loaded');
        
        try {
            $token = @unserialize($tokenData);
            if ($token && is_object($token)) {
                debugLog('Token unserialized: ' . get_class($token));
                if (method_exists($token, 'getUser')) {
                    $user = $token->getUser();
                    if (is_object($user) && method_exists($user, 'getEmail')) {
                        $email = $user->getEmail();
                        debugLog('Email from getEmail(): ' . $email);
                        return $email;
                    }
                    // ユーザーが文字列の場合（ユーザー名 = メールアドレス）
                    if (is_string($user) && filter_var($user, FILTER_VALIDATE_EMAIL)) {
                        debugLog('Email from string user: ' . $user);
                        return $user;
                    }
                }
                if (method_exists($token, 'getUsername')) {
                    $username = $token->getUsername();
                    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                        debugLog('Email from getUsername(): ' . $username);
                        return $username;
                    }
                }
            }
        } catch (\Exception $e) {
            debugLog('Unserialize exception: ' . $e->getMessage());
        }
    }
    
    // 方式2: 正規表現でメールアドレスを抽出
    return extractEmailByRegex($tokenData);
}

/**
 * 正規表現でシリアライズされたデータからメールアドレスを抽出する
 * 
 * EC-CUBEのCustomerエンティティのシリアライズ形式:
 * s:XX:"email@example.com" のパターンでメールアドレスが含まれる
 */
function extractEmailByRegex($data) {
    debugLog('--- Trying regex extraction ---');
    
    // PHPシリアライズ形式のメールアドレスを探す
    // s:N:"user@example.com"; の形式
    if (preg_match_all('/s:\d+:"([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})"/', $data, $matches)) {
        debugLog('Found emails by regex: ' . implode(', ', $matches[1]));
        // 最初に見つかったメールアドレスを返す
        return $matches[1][0];
    }
    
    // プレーンテキストのメールアドレスも試す
    if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $data, $match)) {
        debugLog('Found email by plain regex: ' . $match[0]);
        return $match[0];
    }
    
    debugLog('No email found by regex');
    return null;
}

/**
 * session_start()を使ったセッション読み取りアプローチ
 */
function trySessionStartApproach($savePath, $sessionId) {
    debugLog('--- Trying session_start approach ---');
    
    // 既存のセッションを閉じる
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // セッション名を設定
    session_name(ECCUBE_SESSION_NAME);
    
    // セッションIDを設定
    session_id($sessionId);
    
    // セッション保存先を設定
    if ($savePath && is_dir($savePath)) {
        session_save_path($savePath);
        debugLog('Set save path: ' . $savePath);
    }
    
    // セッションを開始（既存のセッションデータを読み込む）
    $started = @session_start([
        'use_cookies' => 0,      // Cookieを送信しない（読み取り専用）
        'use_only_cookies' => 0,
        'cache_limiter' => '',   // キャッシュヘッダーを送信しない
    ]);
    
    debugLog('Session started: ' . ($started ? 'YES' : 'NO'));
    debugLog('Session ID: ' . session_id());
    
    if ($started) {
        debugLog('$_SESSION keys: ' . implode(', ', array_keys($_SESSION)));
        
        $securityToken = null;
        
        // Symfonyのセッション構造
        if (isset($_SESSION['_sf2_attributes']['_security_customer'])) {
            $securityToken = $_SESSION['_sf2_attributes']['_security_customer'];
            debugLog('Found _security_customer in _sf2_attributes');
        } elseif (isset($_SESSION['_security_customer'])) {
            $securityToken = $_SESSION['_security_customer'];
            debugLog('Found _security_customer at top level');
        }
        
        // セッションを閉じる（書き込みしない）
        session_abort();
        
        if ($securityToken) {
            return extractEmailFromToken($securityToken);
        }
        
        // セッションデータ全体からメールを探す
        $serialized = serialize($_SESSION);
        return extractEmailByRegex($serialized);
    }
    
    return null;
}
