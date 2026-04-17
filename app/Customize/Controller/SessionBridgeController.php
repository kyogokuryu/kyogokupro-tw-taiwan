<?php

/**
 * Session Bridge Controller
 * 
 * SalesDash（salesdash.buzzdrop.co.jp）からのリダイレクトを受け、
 * EC-CUBEのログインセッションを確認し、ログイン中のユーザーの
 * メールアドレスをパラメータとしてSalesDashにリダイレクトバックする。
 * 
 * フロー:
 * 1. SalesDash /tw/cart → ログイン未識別
 * 2. → tw.kyogokupro.com/session_bridge?return_url=https://salesdash.buzzdrop.co.jp/tw/cart
 * 3. EC-CUBEがセッション確認（ファーストパーティCookie）
 * 4. ログイン中 → return_url?email=xxx にリダイレクト
 *    未ログイン → return_url?no_session=1 にリダイレクト
 * 
 * セキュリティ:
 * - return_urlはsalesdash.buzzdrop.co.jpドメインのみ許可
 * - emailはURLパラメータで渡すが、公開情報（メールアドレス）のみ
 * - HMAC署名でリダイレクトの正当性を検証
 * 
 * 作成日: 2026-04-17
 */

namespace Customize\Controller;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SessionBridgeController extends AbstractController
{
    // HMAC署名用の秘密鍵（SalesDash側と共有）
    const BRIDGE_SECRET = 'kg_tw_session_bridge_2026';

    // 許可するリダイレクト先ドメイン
    const ALLOWED_DOMAINS = [
        'salesdash.buzzdrop.co.jp',
    ];

    /**
     * セッションブリッジ - EC-CUBEログイン状態をSalesDashに伝える
     * 
     * @Route("/session_bridge", name="session_bridge")
     */
    public function bridge(Request $request)
    {
        $returnUrl = $request->query->get('return_url', '');

        // return_urlのバリデーション
        if (!$this->isAllowedReturnUrl($returnUrl)) {
            log_info('[SessionBridge] Invalid return_url: ' . $returnUrl);
            return new RedirectResponse('/');
        }

        // EC-CUBEのログインセッションを確認
        $Customer = $this->getUser();

        if ($Customer && is_object($Customer) && method_exists($Customer, 'getEmail')) {
            // ログイン中 → emailとHMAC署名付きでリダイレクト
            $email = $Customer->getEmail();
            $timestamp = time();
            $signature = $this->generateSignature($email, $timestamp);

            $separator = (strpos($returnUrl, '?') !== false) ? '&' : '?';
            $redirectUrl = $returnUrl . $separator . http_build_query([
                'email' => $email,
                'ts' => $timestamp,
                'sig' => $signature,
            ]);

            log_info('[SessionBridge] Logged in user: ' . $email . ' → redirect to SalesDash');
            return new RedirectResponse($redirectUrl);
        }

        // 未ログイン → no_session=1 でリダイレクトバック
        $separator = (strpos($returnUrl, '?') !== false) ? '&' : '?';
        $redirectUrl = $returnUrl . $separator . 'no_session=1';

        log_info('[SessionBridge] No login session → redirect back with no_session');
        return new RedirectResponse($redirectUrl);
    }

    /**
     * return_urlが許可されたドメインかチェック
     */
    private function isAllowedReturnUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return false;
        }

        return in_array($parsed['host'], self::ALLOWED_DOMAINS, true);
    }

    /**
     * HMAC署名を生成
     */
    private function generateSignature(string $email, int $timestamp): string
    {
        $data = $email . ':' . $timestamp;
        return hash_hmac('sha256', $data, self::BRIDGE_SECRET);
    }
}
