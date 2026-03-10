<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/05
 */

namespace Plugin\KokokaraSelect\EventSubscriber;


use Eccube\Event\TemplateEvent;
use Plugin\KokokaraSelect\Config\ConfigSetting;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigService;
use Plugin\KokokaraSelect\Service\TwigRenderService\EventSubscriber\TwigRenderTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartEventSubscriber implements EventSubscriberInterface
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

    public function onCartIndexTemplate(TemplateEvent $event)
    {
        $this->initRenderService($event);

        // 数量変更ボタンカット
        $eachChild = $this->twigRenderService
            ->eachChildBuilder()
            ->findThis()
            ->targetFindAndIndexKey('#kokokara_select_up_down_', 'kokokaraSelectIndex2')
            ->setInsertModeReplaceWith();

        $this->twigRenderService
            ->eachBuilder()
            ->find('.ec-cartRow__amountColumn')
            ->find('.ec-cartRow__amountUpDown')
            ->setEachIndexKey('kokokaraSelectIndex2')
            ->each($eachChild->build());


        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_SELECT_ITEM_VIEW_CART)) {
            // 表示有効

            // 選択商品情報追加
            $eachChild = $this->twigRenderService
                ->eachChildBuilder()
                ->findThis()
                ->targetFindAndIndexKey('#kokokara_select_group_', 'kokokaraSelectIndex')
                ->setInsertModeAppend();

            $this->twigRenderService
                ->eachBuilder()
                ->find('.ec-cartRow')
                ->find('.ec-cartRow__summary')
                ->setEachIndexKey('kokokaraSelectIndex')
                ->each($eachChild->build());

            $this->addTwigRenderSnippet(
                '@KokokaraSelect/default/Cart/index_ex.twig'
            );

        } else {
            // 数量変更ボタンの停止のみ反映
            $this->addTwigRenderSnippet(
                '@KokokaraSelect/default/Cart/index_ex_min.twig'
            );
        }

    }

    public static function getSubscribedEvents()
    {
        return [
            'Cart/index.twig' => ['onCartIndexTemplate', -30],
        ];
    }
}
