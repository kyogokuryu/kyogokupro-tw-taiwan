<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/18
 */

namespace Plugin\PinpointSale\EventSubscriber;


use Eccube\Event\TemplateEvent;
use Plugin\PinpointSale\Config\ConfigSetting;
use Plugin\PinpointSale\Config\ConfigSupportTrait;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigService;
use Plugin\PinpointSale\Service\TwigRenderService\TwigRenderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartEventSubscriber implements EventSubscriberInterface
{

    /** @var TwigRenderService */
    protected $twigRenderService;

    /** @var ConfigService */
    protected $configService;

    use ConfigSupportTrait;

    /**
     * CartEventSubscriber constructor.
     * @param TwigRenderService $twigRenderService
     * @param ConfigService $configService
     */
    public function __construct(
        TwigRenderService $twigRenderService,
        ConfigService $configService
    )
    {
        $this->twigRenderService = $twigRenderService;
        $this->configService = $configService;
    }

    /**
     * カートテンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateCartIndex(TemplateEvent $event)
    {

        // タイムセール名称設定
        $this->setDiscountName($event);

        if (!$this->configService->isKeyBool(ConfigSetting::SETTING_KEY_CART_VIEW)) {
            return;
        }

        $this->twigRenderService->initRenderService($event);

        $eachChild = $this->twigRenderService
            ->eachChildBuilder()
            ->findThis()
            ->targetFindAndIndexKey('#pinpoint_sale_price_', 'pinpointSaleIndex')
            ->setInsertModeAppend();

        $this->twigRenderService
            ->eachBuilder()
            ->find('.ec-cartRow')
            ->find('.ec-cartRow__summary')
            ->setEachIndexKey('pinpointSaleIndex')
            ->each($eachChild->build());

        $this->twigRenderService->addSupportSnippet(
            '@PinpointSale/default/Cart/index_ex.twig'
        );
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
            'Cart/index.twig' => ['onTemplateCartIndex'],
        ];
    }
}
