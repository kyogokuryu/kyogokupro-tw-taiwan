<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ECCUBE4LineIntegration\Service;

use Eccube\Common\Constant;
use Eccube\Entity\CartItem;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\CartRepository;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Entity\BaseInfo;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationRepository;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationSettingRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;

use DateTimeImmutable;
use Exception;

class NotifyMailService
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CartRepository
     */
    protected $cartRepository;

    /**
     * @var BaseInfo
     */
    protected $baseInfo;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Packages
     */
    protected $assetManager;

    /**
     * @var LineIntegrationRepository
     */
    protected $lineIntegrationRepository;

    /**
     * @var LineIntegrationSettingRepository
     */
    protected $lineIntegrationSettingRepository;

    public function __construct(
        CustomerRepository $customerRepository = null,
        CartRepository $cartRepository = null,
        BaseInfoRepository $baseInfoRepository = null,
        Router $router = null,
        Packages $assetManager = null,
        LineIntegrationRepository $lineIntegrationRepository = null,
        LineIntegrationSettingRepository $lineIntegrationSettingRepository = null ,
        ContainerInterface $container = null
    ) {
        $this->customerRepository = $customerRepository;
        $this->cartRepository = $cartRepository;
        $this->baseInfo = $baseInfoRepository->get();
        $this->router = $router;
        $this->assetManager = $assetManager;
        $this->lineIntegrationRepository = $lineIntegrationRepository;
        $this->lineIntegrationSettingRepository = $lineIntegrationSettingRepository;
        $this->container = $container;
    }

    /**
     * かご落ち定期送信を実行: 顧客にかご落ちメールを送信する
     *
     * @param bool $isSendReportMail
     */
    public function executeProduction()
    {
        $this->sendEachNotifyMail(false);
    }

    /**
     * かご落ち動作確認を実行: 顧客でなく管理者宛てに送信する
     *
     * @param bool $isSendReportMail
     * @throws Exception
     */
    public function executeOperationCheck()
    {
        throw new Exception("Not implemented error: LINE連携プラグインでは動作確認は未実装");
    }

    /**
     * 対象の顧客に送るかご落ちメール作成し、送信する
     *
     * @param bool $isSendOnlyToAdmin 動作確認モードのとき true にするフラグ
     * @return array 正しく送信できた件数と、エラーが発生した件数の結果を配列で返す
     */
    private function sendEachNotifyMail(/*unused*/bool $isSendOnlyToAdmin)
    {
        // 各設定の取得
        $config = $this->lineIntegrationSettingRepository->find(1);
        if (is_null($config)) {
            log_error("[LineIntegration] 設定を取得できませんでした");
            return [];
        }
        if (!$config->getCartNotifyIsEnabled()) {
            // 正常系ではEntryPointCommand.phpで弾かれるが、念のためここでも確認
            log_error("[LineIntegration] かご落ち機能が無効に設定されています");
            return [];
        }
        $accessToken = $config->getLineAccessToken();
        $pastDayToNotify = $config->getCartNotifyPastDayToNotify();
        $maxCartItem = $config->getCartNotifyMaxCartItemCount();

        // 各設定のバリデーション
        if ($accessToken === "") {
            log_error("[LineIntegration] accessTokenが未設定です");
            return [];
        }
        if (is_null($pastDayToNotify) || is_null($maxCartItem)) {
            log_error("[LineIntegration] かご落ち機能に関する設定項目が未設定です");
            return [];
        }

        $successCount = 0;
        $errorCount = 0;

        // かご落ち対象の各顧客にメールを送信
        $targetCustomers = $this->getNotifyTargetCustomers($pastDayToNotify);
        foreach ($targetCustomers as $customer) {

            log_info('かご落ち対象の各顧客にpushを送信' . $customer . '');

            try {
                $content = $this->generateMessageContent($customer, $maxCartItem);

                $this->sendLineMessage($accessToken, $content);
            } catch (Exception $e) {
                $errorCount += 1;
                log_error("[LineIntegration] error: " . $e->getMessage(), [$customer]);
                continue;
            }

            log_debug("[LineIntegration] message is sent", [$customer]);
            $successCount += 1;
        }

        return [
            "successCount" => $successCount,
            "errorCount" => $errorCount,
        ];
    }

    /**
     * 全顧客からかご落ちメール送信対象の顧客を取得する
     *
     * @param int $pastDayToNotify
     * @return array
     */
    public function getNotifyTargetCustomers(int $pastDayToNotify)
    {
        try {
            $nowDateTime = new DateTimeImmutable();
        } catch (Exception $e) {
            // warning対策: 引数省略時にDateTimeImmutableは例外を発生しないが念のため
            log_critical("[LineIntegration] Exception: " . $e->getMessage());
            return [];
        }
        $startDateTime = $nowDateTime->modify("-{$pastDayToNotify} days")->setTime(0, 0, 0);
        $endDateTime = $startDateTime->modify("+1 days");
        log_info("[LineIntegration] nowDateTime", [$nowDateTime]);
        log_debug("[LineIntegration] startDateTime, endDateTime", [$startDateTime, $endDateTime]);

        $customers = $this->customerRepository->getNonWithdrawingCustomers();
        $targetCustomers = [];
        foreach ($customers as $customer) {
            $cart = $this->cartRepository->findOneBy(['Customer' => $customer]);
            if (is_null($cart)) {
                log_debug("[LineIntegration] cart is null", [$customer]);
                continue;
            }

            $cartItemCount = $cart->getCartItems()->count();
            // `!is_null($cart) && $cartItemCount == 0`を満たす例が存在するかは分からないが念のため
            if ($cartItemCount == 0) {
                log_debug("[LineIntegration] cartItemCount is zero", [$customer]);
                continue;
            }

            $cartUpdateDate = $cart->getUpdateDate();
            $isWithinRange = $startDateTime <= $cartUpdateDate && $cartUpdateDate < $endDateTime;
            if (!$isWithinRange) {
                log_debug("[LineIntegration] updateDate is out of range", [$customer, $cartUpdateDate]);
                continue;
            }

            $maxItemCount = 5; // カート内に有効な商品があるかを確かめるだけなので、1以上の適当な値を指定
            $filteredCartItems = $this->getFilteredCartItems($maxItemCount, $customer);
            if (count($filteredCartItems) <= 0) {
                log_debug("[LineIntegration] filteredCartItems is zero", [$customer]);
                continue;
            }

            // LINE連携の確認
            $lineIntegration = $this->lineIntegrationRepository->findOneBy([
                'customer_id' => $customer->getId(),
                'line_notification_flg' => Constant::ENABLED,
            ]);
            if (is_null($lineIntegration)) {
                log_debug("[LineIntegration] line integration is null", [$customer]);
                continue;
            }

            $targetCustomers[] = $customer;
        }

        return $targetCustomers;
    }

    /**
     * LINEのメッセージ本文を生成する
     *
     * @warm 商品画像のURL生成が多少強引なので、ポートが80/443以外など特殊な環境では要注意
     *
     * @param Customer $customer
     * @param int $maxCartItem
     * @return array
     */
    private function generateMessageContent($customer, $maxCartItem)
    {
        $lineIntegration = $this->lineIntegrationRepository->findOneBy([
            'customer_id' => $customer->getId(),
            'line_notification_flg' => Constant::ENABLED,
        ]);
        $lineUserId = $lineIntegration->getLineUserId();
        $cartItems = $this->getFilteredCartItems($maxCartItem, $customer);

        $this->router = $this->container->get('router');

        $cartItems = array_map(function (CartItem $item) {
            $request = $this->router->getContext();
            $product = $item->getProductClass()->getProduct();
            $url = $this->router->generate('product_detail', [
                'id' => $product->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $img = $product->getMainListImage();
            $img = is_null($img) ? "no_image_product.png" : $img->getFileName();
            $img = $this->assetManager->getUrl($img, "save_image");
            $img = $request->getScheme() . '://' . $request->getHost() . $request->getBaseUrl() . $img;
            return [
                "imageUrl" => $img,
                "action" => [
                    "type" => "uri",
                    "label" => "この商品を見る",
                    "uri" => $url,
                ]
            ];
        }, $cartItems);
        $cartUrl = $this->router->generate('cart_line_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return [
            "to" => [
                $lineUserId,
            ],
            "messages" => [
                [
                    "type" => "text",
                    "text" => "次の商品がカートに入っています。お買い忘れはありませんか？\n" . $cartUrl,
                ],
                [
                    "type" => "template",
                    "altText" => "次の商品がカートに入っています。お買い忘れはありませんか？",
                    "template" => [
                        "type" => "image_carousel",
                        "columns" => $cartItems,
                    ]
                ],
            ]
        ];
    }

    /**
     * カート内商品選択ロジック
     *
     * 顧客のカート内商品を、最大数分に絞って取得する
     *
     * @param int $maxItemCount
     * @param Customer $customer
     * @return array RecommendProductの配列
     */
    private function getFilteredCartItems($maxItemCount, $customer)
    {
        if ($maxItemCount <= 0) {
            return [];
        }

        $cart = $this->cartRepository->findOneBy(['Customer' => $customer]);
        if (is_null($cart)) {
            return [];
        }

        return $cart->getCartItems()
            ->filter(function (CartItem $item) {
                // 公開状態の商品のみに限定。ProductClass::isEnableがdeprecatedなので下記コードで代替
                return $item->getProductClass()->getProduct()->getStatus()->getId() === ProductStatus::DISPLAY_SHOW;
            })
            ->filter(function (CartItem $item) {
                // 在庫がある商品のみに限定
                return $item->getProductClass()->getStockFind();
            })
            ->slice(0, $maxItemCount);
    }

    /**
     * 管理者宛ての完了メールを生成する
     *
     * @param array $execResult 完了メールに記載する内容を含む配列
     * @param bool $isCheckMode
     * @throws Exception
     */
    private function sendReportMail($execResult, bool $isCheckMode)
    {
        throw new Exception("Not implemented error: LINE連携プラグインでは完了メールは未実装");
    }

    /**
     * LINEでメッセージを送信する
     *
     * @param $accessToken
     * @param $postData
     * @see LineIntegrationAdminController::message()
     */
    private function sendLineMessage($accessToken, $postData)
    {
        log_info("postData", $postData);
        $ch = curl_init("https://api.line.me/v2/bot/message/multicast");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charser=UTF-8',
            'Authorization: Bearer ' . $accessToken
        ));

        // デバッグ情報
        $info = curl_getinfo($ch);
        log_info(sprintf("通信内容（実行前）：%s", print_r($info, true)));

        // 通信実行
        $curlResult = curl_exec($ch);

        // デバッグ情報
        $curlResultHeader = substr($curlResult, 0, $info["header_size"]);
        log_info(sprintf("レスポンスヘッダ：%s", print_r($curlResultHeader, true)));

        // デバッグ情報
        $curlResultBody = substr($curlResult, $info["header_size"]);
        log_info(sprintf("レスポンスボディ：%s", print_r($curlResultBody, true)));

        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        // HTTPエラーハンドリング
        if (CURLE_OK !== $errno) {
            log_error(sprintf("メッセージ送信エラー(HTTP：CURL_ERRORNO:[%s] CURL_ERROR:[%s]", $errno, $error));
        }

        // APIエラーハンドリング
        $resultJson = json_decode($curlResult, true);
        if (!empty($resultJson)) {
            log_error(sprintf("メッセージ送信エラー(LINE API)：[%s]", json_encode($resultJson, true)));
        }
    }
}
