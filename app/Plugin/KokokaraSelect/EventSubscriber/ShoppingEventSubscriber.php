<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/24
 */

namespace Plugin\KokokaraSelect\EventSubscriber;


use Eccube\Event\TemplateEvent;
use Plugin\KokokaraSelect\Config\ConfigSetting;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigService;
use Plugin\KokokaraSelect\Service\TwigRenderService\EventSubscriber\TwigRenderTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShoppingEventSubscriber implements EventSubscriberInterface
{

    use TwigRenderTrait;

    /** @var ConfigService */
    protected $configService;

    public function __construct(
        ConfigService $configService
    )
    {
        $this->configService = $configService;
    }

    /**
     * ご注文手続きテンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateShopping(TemplateEvent $event)
    {
        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_SELECT_ITEM_VIEW_SHOPPING)) {
            $this->viewKsSelectItem($event);
        }
    }

    /**
     * ご注文内容確認テンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateShoppingConfirm(TemplateEvent $event)
    {
        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_SELECT_ITEM_VIEW_CONFIRM)) {
            $this->viewKsSelectItem($event);
        }
    }

    private function viewKsSelectItem(TemplateEvent $event)
    {
        $this->initRenderService($event);

        $child = $this->twigRenderService
            ->eachChildBuilder()
            ->findThis()
            ->targetFindAndIndexKey('#kokokara_select_item_', 'kokokaraSelectIndex')
            ->setInsertModeAppend();

        $this->twigRenderService
            ->eachBuilder()
            ->find('.ec-orderDelivery__item')
            ->find('.ec-imageGrid')
            ->find('.ec-imageGrid__content')
            ->setEachIndexKey('kokokaraSelectIndex')
            ->each($child->build());

        $this->addTwigRenderSnippet('@KokokaraSelect/default/Shopping/index_ex.twig');
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => ['onTemplateShopping', -20],
            'Shopping/confirm.twig' => ['onTemplateShoppingConfirm', -20],
        ];
    }
}
