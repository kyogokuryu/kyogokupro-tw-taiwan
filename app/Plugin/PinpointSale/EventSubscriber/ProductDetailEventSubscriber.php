<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/25
 */

namespace Plugin\PinpointSale\EventSubscriber;


use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Plugin\PinpointSale\Config\ConfigSetting;
use Plugin\PinpointSale\Config\ConfigSupportTrait;
use Plugin\PinpointSale\Service\PinpointSaleService;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigService;
use Plugin\PinpointSale\Service\TwigRenderService\TwigRenderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductDetailEventSubscriber implements EventSubscriberInterface
{
    /** @var TwigRenderService */
    protected $twigRenderService;

    /** @var ConfigService */
    protected $configService;

    /** @var PinpointSaleService */
    protected $pinpointSaleService;

    use ConfigSupportTrait;

    /**
     * ProductDetailEventSubscriber constructor.
     * @param TwigRenderService $twigRenderService
     * @param ConfigService $configService
     * @param PinpointSaleService $pinpointSaleService
     */
    public function __construct(
        TwigRenderService $twigRenderService,
        ConfigService $configService,
        PinpointSaleService $pinpointSaleService
    )
    {
        $this->twigRenderService = $twigRenderService;
        $this->configService = $configService;
        $this->pinpointSaleService = $pinpointSaleService;
    }

    /**
     * 商品詳細 テンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateProductDetail(TemplateEvent $event)
    {

        // タイムセール名称設定
        $this->setDiscountName($event);

        $this->twigRenderService->initRenderService($event);

        /** @var Product $Product */
        $Product = $event->getParameter('Product');

        $viewFile = null;
        $jsFile = null;

        // 表示追加判定
        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_PRODUCE_DETAIL_VIEW)) {

            // 表示場所調整
            if ($Product->getPrice01Min()) {
                if ($Product->getPrice01Max()) {
                    $findKey = '.ec-productRole__priceRegularTax';
                } else {
                    $findKey = '.ec-productRole__priceRegular';
                }
            } else {
                $findKey = '.ec-productRole__tags';
            }

            $this->twigRenderService
                ->insertBuilder()
                ->find($findKey)
                ->eq(0)
                ->setTargetId('ec-productRole__pinpoint_sale')
                ->setInsertModeAfter();

            $viewFile = '@PinpointSale/default/Product/detail_add.twig';
        }

        // jS追加判定
        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_PRODUCT_DETAIL_JS)) {

            $jsFile = '@PinpointSale/default/Product/detail_add_js.twig';
        }

        $this->twigRenderService->addSupportSnippet($viewFile, $jsFile);
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
            "Product/detail.twig" => ['onTemplateProductDetail'],
        ];
    }
}
