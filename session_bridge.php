<?php
/**
 * Session Bridge - 独立PHPスクリプト版
 * 
 * EC-CUBEのSymfonyルーティングに依存せず、直接EC-CUBEカーネルをブートして
 * ログインセッションを確認し、SalesDashにリダイレクトバックする。
 * 
 * URL: https://tw.kyogokupro.com/session_bridge.php?return_url=...
 * 
 * フロー:
 * 1. SalesDash /tw/cart → ログイン未識別
 * 2. → tw.kyogokupro.com/session_bridge.php?return_url=https://salesdash.buzzdrop.co.jp/tw/cart
 * 3. EC-CUBEカーネルブート → セッション確認
 * 4. ログイン中 → return_url?email=xxx&ts=xxx&sig=xxx にリダイレクト
 *    未ログイン → return_url?no_session=1 にリダイレクト
 * 
 * セキュリティ:
 * - return_urlはsalesdash.buzzdrop.co.jpドメインのみ許可
 * - HMAC署名でリダイレクトの正当性を検証
 * 
 * 作成日: 2026-04-17
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// HMAC署名用の秘密鍵
define('BRIDGE_SECRET', 'kg_tw_session_bridge_2026');

// 許可するリダイレクト先ドメイン
$allowedDomains = ['salesdash.buzzdrop.co.jp'];

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

// EC-CUBEのオートローダーとカーネルをブート
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    // vendorがない場合はno_sessionで返す
    $sep = (strpos($returnUrl, '?') !== false) ? '&' : '?';
    header('Location: ' . $returnUrl . $sep . 'no_session=1');
    exit;
}

require $autoload;

// .envの読み込み
if (file_exists(__DIR__ . '/.env')) {
    if (class_exists('Dotenv\\Dotenv')) {
        // Dotenv v3+
        if (method_exists('Dotenv\\Dotenv', 'createImmutable')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
        } else {
            $dotenv = new \Dotenv\Dotenv(__DIR__);
        }
        try {
            $dotenv->load();
        } catch (\Exception $e) {
            // ignore
        }
    }
}

try {
    // EC-CUBEカーネルをブート
    $kernel = new \Eccube\Kernel('prod', false);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    
    // Symfonyのセッションからユーザー情報を取得
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    
    // セッションを開始（既存のセッションCookieを使用）
    $session = $container->get('session');
    $request->setSession($session);
    
    // TokenStorageからログインユーザーを取得
    $tokenStorage = $container->get('security.token_storage');
    $token = $tokenStorage->getToken();
    
    $email = null;
    
    if ($token && $token->getUser() && is_object($token->getUser())) {
        $user = $token->getUser();
        if (method_exists($user, 'getEmail')) {
            $email = $user->getEmail();
        }
    }
    
    $kernel->shutdown();
    
} catch (\Exception $e) {
    // エラー時はno_sessionで返す
    $sep = (strpos($returnUrl, '?') !== false) ? '&' : '?';
    header('Location: ' . $returnUrl . $sep . 'no_session=1');
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
