<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/17
 */

namespace Plugin\KokokaraSelect\EventSubscriber;


use Eccube\Entity\Product;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\KokokaraSelect\Config\ConfigSetting;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigService;
use Plugin\KokokaraSelect\Service\TwigRenderService\EventSubscriber\TwigRenderTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductEventSubscriber implements EventSubscriberInterface
{

    use TwigRenderTrait;

    /** @var ConfigService */
    protected $configService;

    /** @var KsService */
    protected $ksService;

    public function __construct(
        ConfigService $configService,
        KsService $ksService
    )
    {
        $this->configService = $configService;
        $this->ksService = $ksService;
    }

    /**
     * @param TemplateEvent $event
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function onProductListTemplate(TemplateEvent $event)
    {
        $source = $event->getSource();

        // カートボタン
        $searchKey = '<div class="ec-productRole__btn">';

        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_CART_BUTTON_VIEW)) {
            $replaceTemplate = '@KokokaraSelect\default\Product\replace_list_cart_btn.twig';
        } else {
            $replaceTemplate = '@KokokaraSelect\default\Product\replace_list_cart.twig';
        }

        $source = $this->templateReplace($source, $searchKey, $replaceTemplate);

        // 数量選択
        $searchKey = '<div class="ec-numberInput">';
        $replaceTemplate = '@KokokaraSelect\default\Product\replace_list_quantity.twig';

        $event->setSource($this->templateReplace($source, $searchKey, $replaceTemplate));
    }

    /**
     * 商品詳細テンプレート
     *
     * @param TemplateEvent $event
     */
    public function onProductDetailTemplate(TemplateEvent $event)
    {

        /** @var Product $product */
        $product = $event->getParameter('Product');

        if ($this->ksService->isKsProduct($product)) {

            $ksProduct = $product->getKsProduct();
            if ($ksProduct->isDirectSelect()) {
                // 固定セット商品の明細表示
                $this->initRenderService($event);

                $this->createInsertBuilder()
                    ->find('.ec-productRole__price')
                    ->setTargetId('kokokara_select_items')
                    ->setInsertModeAfter();

                $this->addTwigRenderSnippet('@KokokaraSelect\default\Product\detail_add.twig');

                $event->addSnippet('@KokokaraSelect\default\Product\detail_add_css.twig');
            }
        }
    }

    /**
     * @param TemplateEvent $event
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function onCartInRecommendTemplate(TemplateEvent $event)
    {
        $source = $event->getSource();

        $searchKey = '<div class="cart_in_recommend_btn">';

        if ($this->configService->isKeyBool(ConfigSetting::SETTING_KEY_CART_BUTTON_VIEW)) {
            $replaceTemplate = '@KokokaraSelect\default\Product\replace_cart_in_recommend_btn.twig';
        } else {
            $replaceTemplate = '@KokokaraSelect\default\Product\replace_cart_in_recommend.twig';
        }


        $event->setSource($this->templateReplace($source, $searchKey, $replaceTemplate));
    }

    /**
     * @param $source
     * @param $searchKey
     * @param $replaceTemplate
     * @return string|string[]
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function templateReplace($source, $searchKey, $replaceTemplate)
    {
        $replace = $this->twigRenderService->readTemplate($replaceTemplate, [], false);
        $source = str_replace($searchKey, $replace, $source);

        return $source;
    }

    /**
     * タイムセール連携
     *
     * @param EventArgs $event
     */
    public function onPinpointSaleHelperHookRoute(EventArgs $event)
    {
        $hookRoute = $event->getArgument('hookRoute');
        $hookRoute['kokokara_select_list'] = 1;
        $hookRoute['kokokara_select_list_edit'] = 1;
        $event->setArgument('hookRoute', $hookRoute);
    }

    public static function getSubscribedEvents()
    {
        return [
            // 商品一覧テンプレート
            'Product/list.twig' => ['onProductListTemplate'],
            // 商品詳細テンプレート
            'Product/detail.twig' => ['onProductDetailTemplate'],
            // カートイン時レコメンド
            '@CartInRecommend/default/Product/recommend.twig' => ['onCartInRecommendTemplate'],
            // タイムセール
            'pinpoint_sale.helper.hook_route' => ['onPinpointSaleHelperHookRoute'],
        ];
    }
}
