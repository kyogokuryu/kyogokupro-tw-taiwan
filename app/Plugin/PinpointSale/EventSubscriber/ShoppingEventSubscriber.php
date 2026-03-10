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

class ShoppingEventSubscriber implements EventSubscriberInterface
{

    /** @var TwigRenderService */
    protected $twigRenderService;

    /** @var ConfigService */
    protected $configService;

    use ConfigSupportTrait;

    /**
     * ShoppingEventSubscriber constructor.
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
     * ご注文手続きテンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateShopping(TemplateEvent $event)
    {
        // タイムセール名称設定
        $this->setDiscountName($event);

        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_SHOPPING_VIEW)) {
            $this->viewPinpointSale($event);
        }
    }

    /**
     * ご注文確認テンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateShoppingConfirm(TemplateEvent $event)
    {
        // タイムセール名称設定
        $this->setDiscountName($event);

        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_CONFIRM_VIEW)) {
            $this->viewPinpointSale($event);
        }
    }

    /**
     * タイムセール表示追加
     *
     * @param TemplateEvent $event
     */
    private function viewPinpointSale(TemplateEvent $event)
    {

        $this->twigRenderService->initRenderService($event);

        $child = $this->twigRenderService
            ->eachChildBuilder()
            ->findThis()
            ->targetFindAndIndexKey('#pinpoint_sale_price_', 'pinpointSaleIndex')
            ->setInsertModeAppend();

        $this->twigRenderService
            ->eachBuilder()
            ->find('.ec-orderDelivery__item')
            ->find('.ec-imageGrid')
            ->find('.ec-imageGrid__content')
            ->setEachIndexKey('pinpointSaleIndex')
            ->each($child->build());

        $this->twigRenderService->addSupportSnippet('@PinpointSale/default/Shopping/index_ex.twig');
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
            'Shopping/index.twig' => ['onTemplateShopping'],
            'Shopping/confirm.twig' => ['onTemplateShoppingConfirm'],
        ];
    }
}
