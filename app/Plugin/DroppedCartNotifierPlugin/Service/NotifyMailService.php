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

namespace Plugin\DroppedCartNotifierPlugin\Service;

use Eccube\Entity\CartItem;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\CartRepository;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Entity\BaseInfo;
use Plugin\DroppedCartNotifierPlugin\Repository\DroppedCartNotifierConfigRepository;
use \Swift_Mailer;
use \Swift_Message;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use \Twig_Environment;
use Doctrine\Common\Collections\ArrayCollection;

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
     * @var DroppedCartNotifierConfigRepository
     */
    protected $droppedCartNotifierConfigRepository;

    /**
     * @var RecommendPluginIntegration
     */
    protected $recommendPluginIntegration;

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    public function __construct(
        CustomerRepository $customerRepository = null,
        CartRepository $cartRepository = null,
        BaseInfoRepository $baseInfoRepository = null,
        DroppedCartNotifierConfigRepository $droppedCartNotifierConfigRepository = null,
        RecommendPluginIntegration $recommendPluginIntegration = null,
        Swift_Mailer $mailer = null,
        Twig_Environment $twig = null
    ) {
        $this->customerRepository = $customerRepository;
        $this->cartRepository = $cartRepository;
        $this->baseInfo = $baseInfoRepository->get();
        $this->droppedCartNotifierConfigRepository = $droppedCartNotifierConfigRepository;
        $this->recommendPluginIntegration = $recommendPluginIntegration;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * かご落ち定期送信を実行: 顧客にかご落ちメールを送信する
     *
     * @param bool $isSendReportMail
     */
    public function executeProduction(bool $isSendReportMail = true)
    {
        $result = $this->sendEachNotifyMail(false);

        if ($isSendReportMail) {
            $this->sendReportMail($result, false);
        }
    }

    /**
     * かご落ち動作確認を実行: 顧客でなく管理者宛てに送信する
     *
     * @param bool $isSendReportMail
     */
    public function executeOperationCheck(bool $isSendReportMail = true)
    {
        $result = $this->sendEachNotifyMail(true);

        if ($isSendReportMail) {
            $this->sendReportMail($result, true);
        }
    }

    /**
     * 対象の顧客に送るかご落ちメール作成し、送信する
     *
     * @param bool $isSendOnlyToAdmin 動作確認モードのとき true にするフラグ
     * @return array 正しく送信できた件数と、エラーが発生した件数の結果を配列で返す
     */
    private function sendEachNotifyMail(bool $isSendOnlyToAdmin)
    {
        // 各設定の取得
        $config = $this->droppedCartNotifierConfigRepository->get();
        if (is_null($config)) {
            log_error("[DroppedCartNotifier] 設定を取得できませんでした");
            return [];
        }
        $pastDayToNotify = $config->getPastDayToNotify();
        $maxCartItem = $config->getMaxCartItem();
        $maxRecommendedItem = $config->getMaxRecommendedItem();
        $mailSubject = $config->getMailSubject();

        $successCount = 0;
        $errorCount = 0;

        // かご落ち対象の各顧客にメールを送信
        $targetCustomers = $this->getNotifyTargetCustomers($pastDayToNotify);
        foreach ($targetCustomers as $customer) {
            try {
                $message = $this->generateNotifyMail($customer, $maxCartItem, $maxRecommendedItem, $mailSubject);

                // 確認モードのときは顧客でなく、管理者にかご落ちメールを送る
                if ($isSendOnlyToAdmin) {
                    $message->setTo([$this->baseInfo->getEmail01()]);
                } else {
                    $message
                        ->setTo([$customer->getEmail()])
                        ->setBcc($this->baseInfo->getEmail01());
                }

                $this->mailer->send($message);
                log_debug("[DroppedCartNotifier] send", [$customer]);
            } catch (Exception $e) {
                $errorCount += 1;
                log_error("[DroppedCartNotifier] error: " . $e->getMessage(), [$customer]);
                continue;
            }

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
            // warning対策: 引数省略時にDateTimeImmutableが例外を発生はしないはず
            log_critical("[DroppedCartNotifier] Exception: " . $e->getMessage());
            return [];
        }
        $startDateTime = $nowDateTime->modify("-{$pastDayToNotify} days")->setTime(0, 0, 0);
        $endDateTime = $startDateTime->modify("+1 days");
        log_info("[DroppedCartNotifier] nowDateTime", [$nowDateTime]);
        log_debug("[DroppedCartNotifier] startDateTime, endDateTime", [$startDateTime, $endDateTime]);

        $customers = $this->customerRepository->getNonWithdrawingCustomers();
        $targetCustomers = [];
        foreach ($customers as $customer) {
            $cart = $this->cartRepository->findOneBy(['Customer' => $customer]);
            if (is_null($cart)) {
                log_debug("[DroppedCartNotifier] cart is null", [$customer]);
                continue;
            }

            $cartItemCount = $cart->getCartItems()->count();
            // `!is_null($cart) && $cartItemCount == 0`を満たす例が存在するかは分からないが念のため
            if ($cartItemCount == 0) {
                log_debug("[DroppedCartNotifier] cartItemCount is zero", [$customer]);
                continue;
            }

            $cartUpdateDate = $cart->getUpdateDate();
            $isWithinRange = $startDateTime <= $cartUpdateDate && $cartUpdateDate < $endDateTime;
            if (!$isWithinRange) {
                log_debug("[DroppedCartNotifier] updateDate is out of range", [$customer, $cartUpdateDate]);
                continue;
            }

            $maxItemCount = 5; // カート内に有効な商品があるかを確かめるだけなので、1以上の適当な値を指定
            $filteredCartItems = $this->getFilteredCartItems($maxItemCount, $customer);
            if (count($filteredCartItems) <= 0) {
                log_debug("[DroppedCartNotifier] filteredCartItems is zero", [$customer]);
                continue;
            }

            $targetCustomers[] = $customer;
        }

        return $targetCustomers;
    }

    /**
     * 顧客に送信するかご落ちメール本文を生成する
     *
     * @param Customer $customer
     * @param int $maxCartItem
     * @param int $maxRecommendedItem
     * @param string $mailSubject
     * @return Swift_Message
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function generateNotifyMail(
        Customer $customer,
        int $maxCartItem,
        int $maxRecommendedItem,
        string $mailSubject
    ) {
        // カート内商品・おすすめ商品の取得
        $filteredCartItems = $this->getFilteredCartItems($maxCartItem, $customer);
        $recommendedItems = $this->getRecommendedItems($maxRecommendedItem, $filteredCartItems);

        // メッセージ本文の生成
        $message = (new Swift_Message())
            ->setSubject("[{$this->baseInfo->getShopName()}] {$mailSubject}")
            ->setFrom([$this->baseInfo->getEmail01() => $this->baseInfo->getShopName()])
            ->setReplyTo($this->baseInfo->getEmail03())
            ->setReturnPath($this->baseInfo->getEmail04());

        $context = [
            'customer' => $customer,
            'cartItems' => $filteredCartItems,
            'cartItemCount' => count($filteredCartItems),
            'maxCartItem' => $maxCartItem,
            'recommendedItems' => $recommendedItems,
            'baseInfo' => $this->baseInfo,
        ];
        $plainTextBody = $this->twig->render(
            "DroppedCartNotifierPlugin/Resource/template/mail/notify.twig",
            $context);
        $htmlBody = $this->twig->render(
            "DroppedCartNotifierPlugin/Resource/template/mail/notify.html.twig",
            $context);
        $message
            ->setContentType('text/plain; charset=UTF-8')
            ->setBody($plainTextBody, 'text/plain')
            ->addPart($htmlBody, 'text/html');

        return $message;
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
     * おすすめ商品選択ロジック
     *
     * おすすめ商品を、カート内商品との重複を省いて取得する
     *
     * @param int $maxItemCount
     * @param CartItem[] $cartItems
     * @return array RecommendProductの配列
     */
    private function getRecommendedItems($maxItemCount, $cartItems)
    {
        if ($maxItemCount <= 0) {
            return [];
        }

        $cartProducts = (new ArrayCollection($cartItems))->map(function (CartItem $item) {
            return $item->getProductClass()->getProduct();
        });

        $allRecommends = $this->recommendPluginIntegration->getRecommendedItems() ?? [];
        $allRecommends = new ArrayCollection($allRecommends);

        return $allRecommends
            ->filter(function ($item) use ($cartProducts) {
                return !$cartProducts->contains($item->getProduct());
            })
            ->slice(0, $maxItemCount);
    }

    /**
     * 管理者宛ての完了メールを生成する
     *
     * @param array $execResult 完了メールに記載する内容を含む配列
     * @param bool $isCheckMode
     */
    private function sendReportMail($execResult, bool $isCheckMode)
    {
        log_info("[DroppedCartNotifier] 管理者に完了メールを送信します。", $execResult);

        $shopName = $this->baseInfo->getShopName();
        $successCount = $execResult['successCount'];

        if ($isCheckMode) {
            $content = "${shopName}のお客様へのかご落ちメールを動作確認しました。\n\n送信件数: ${successCount}件";
            $subject = "[${shopName}] かご落ちメール送信完了【動作確認】";
        } else {
            $content = "${shopName}のお客様へのかご落ちメール送信が完了しました。\n\n送信件数: ${successCount}件";
            $subject = "[${shopName}] かご落ちメール送信完了";
        }

        $message = (new Swift_Message())
            ->setSubject($subject)
            ->setFrom([$this->baseInfo->getEmail01() => $shopName])
            ->setTo($this->baseInfo->getEmail01())
            ->setBody($content);
        $this->mailer->send($message);
    }
}