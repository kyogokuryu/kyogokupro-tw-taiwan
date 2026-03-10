<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/24
 */

namespace Plugin\PinpointSale\EventSubscriber;


use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Event\TemplateEvent;
use Plugin\PinpointSale\Config\ConfigSetting;
use Plugin\PinpointSale\Config\ConfigSupportTrait;
use Plugin\PinpointSale\Service\PinpointSaleService;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigService;
use Plugin\PinpointSale\Service\PurchaseFlow\Processor\PinpointSaleDiscountProcessor;
use Plugin\PinpointSale\Service\TwigRenderService\TwigRenderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MypageEventSubscriber implements EventSubscriberInterface
{

    /** @var TwigRenderService */
    protected $twigRenderService;

    /** @var PinpointSaleService */
    protected $pinpointSaleService;

    /** @var ConfigService */
    protected $configService;

    use ConfigSupportTrait;

    /**
     * MypageEventSubscriber constructor.
     * @param TwigRenderService $twigRenderService
     * @param PinpointSaleService $pinpointSaleService
     * @param ConfigService $configService
     */
    public function __construct(
        TwigRenderService $twigRenderService,
        PinpointSaleService $pinpointSaleService,
        ConfigService $configService
    )
    {
        $this->twigRenderService = $twigRenderService;
        $this->pinpointSaleService = $pinpointSaleService;
        $this->configService = $configService;
    }

    /**
     * 注文履歴詳細 テンプレート
     *
     * @param TemplateEvent $event
     * @throws \Exception
     */
    public function onTemplateMypageHistory(TemplateEvent $event)
    {

        // タイムセール名称設定
        $this->setDiscountName($event);

        if (!$this->configService->isKeyBool(ConfigSetting::SETTING_KEY_HISTORY_VIEW)) {
            return;
        }

        $this->twigRenderService->initRenderService($event);

        // 値引き額
        $pinpointDiscounts = [];
        // 現在の値引き額
        $nowPinpointDiscounts = [];
        // タイムセール変更状態
        $pinpointDiscountChange = false;

        /** @var Order $order */
        $order = $event->getParameter('Order');

        /** @var OrderItem $orderItem */
        foreach ($order->getItems() as $orderItem) {

            // 受注よりタイムセール値引き額取得
            if ($orderItem->getProcessorName() == PinpointSaleDiscountProcessor::class) {

                $shipping_id = $orderItem->getShipping()->getId();
                $product_class_id = $orderItem->getProductClass()->getId();
                $key = $shipping_id . '-' . $product_class_id;

                $pinpointDiscounts[$key] = ($orderItem->getPriceIncTax() * $orderItem->getQuantity());
            }
        }

        foreach ($order->getItems() as $orderItem) {

            // 現在のタイムセール値引き額取得
            if ($orderItem->isProduct()) {

                $shipping_id = $orderItem->getShipping()->getId();
                $product_class_id = $orderItem->getProductClass()->getId();
                $key = $shipping_id . '-' . $product_class_id;

                $pinpointSaleItem = $this->pinpointSaleService->getPinpointSaleItem($orderItem->getProductClass());

                if ($pinpointSaleItem && $pinpointSaleItem->isActive()) {
                    // セール価格あり
                    $discountPrice = $pinpointSaleItem->getDiscountPriceIncTax();
                    $discountPriceSubtotal = -1 * ($discountPrice * $orderItem->getQuantity());

                    if (isset($pinpointDiscounts[$key])
                        && $pinpointDiscounts[$key] == $discountPriceSubtotal) {
                        // 購入時点と同じ状態のため表示しない
                        continue;
                    }

                    if ($discountPriceSubtotal > 0) {
                        // 値引ではなく増額のため表示しない
                        continue;
                    }

                    // タイムセール状態に変更有りマーク
                    $pinpointDiscountChange = true;

                } else {
                    // セール外
                    $discountPriceSubtotal = 0;
                }
                $nowPinpointDiscounts[$key] = $discountPriceSubtotal;
            }
        }

        // 受注のタイムセール値引情報
        $event->setParameter('pinpointDiscounts', $pinpointDiscounts);
        // 現在のタイムセール値引情報
        $event->setParameter('nowPinpointDiscounts', $nowPinpointDiscounts);

        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_HISTORY_VIEW)) {

            /* 表示制御 */
            // タイムセールレコード追加
            $childes[] = $this->twigRenderService
                ->eachChildBuilder()
                ->findThis()
                ->find('.ec-imageGrid__content')
                ->targetFindAndIndexKey('#pinpoint_sale_history_', "pinpointSaleIndex")
                ->setInsertModeAppend();

            $this->twigRenderService
                ->eachBuilder()
                ->find('.ec-orderDelivery__item')
                ->setEachIndexKey('pinpointSaleIndex')
                ->each($childes);
        }

        // タイムセール変更状態
        if ($pinpointDiscountChange) {
            // 変更状態通知表示
            $this->twigRenderService
                ->insertBuilder()
                ->find('.ec-orderRole__summary')
                ->eq(0)
                ->setTargetId('pinpoint_sale_message')
                ->setInsertModeAppend();
        }

        $this->twigRenderService->addSupportSnippet('@PinpointSale/default/Mypage/history_ex.twig');

        $event->addAsset('@PinpointSale/default/Mypage/history_ex_css.twig');
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'Mypage/history.twig' => ['onTemplateMypageHistory'],
        ];
    }
}
