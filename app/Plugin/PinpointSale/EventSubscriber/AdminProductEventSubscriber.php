<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/03
 */

namespace Plugin\PinpointSale\EventSubscriber;


use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Plugin\PinpointSale\Form\Helper\FormHelper;
use Plugin\PinpointSale\Service\TwigRenderService\TwigRenderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormView;

class AdminProductEventSubscriber implements EventSubscriberInterface
{

    /** @var TwigRenderService */
    protected $twigRenderService;

    /** @var FormHelper */
    protected $formHelper;

    public function __construct(
        TwigRenderService $twigRenderService,
        FormHelper $formHelper
    )
    {
        $this->twigRenderService = $twigRenderService;
        $this->formHelper = $formHelper;
    }

    /**
     * 商品一覧テンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateProductIndex(TemplateEvent $event)
    {
        $this->twigRenderService->initRenderService($event);

        // タイムセール対象表示
        $child = $this->twigRenderService
            ->eachChildBuilder()
            ->findAndDataKey('#ex-product-', 'pinpoint_sale_product_id')
            ->find('td')
            ->eq(5)
            ->targetFindThis()
            ->setInsertModeAppend();

        $this->twigRenderService
            ->eachBuilder()
            ->find('.pinpoint_sale_price_modal')
            ->each($child->build());

        // モーダル追加
        $this->twigRenderService->insertBuilder()
            ->find('#productClassesModal')
            ->setTargetId('pinpointSaleModal')
            ->setInsertModeAfter();

        $this->twigRenderService->addSupportSnippet(
            '@PinpointSale/admin/Product/index/pinpoint_sale.twig',
            '@PinpointSale/admin/Product/index/pinpoint_sale_js.twig'
        );

        // Style追加
        $event->addAsset('@PinpointSale/admin/Product/index/pinpoint_sale_css.twig');
    }

    /**
     * 商品 商品登録
     *
     * @param TemplateEvent $event
     */
    public function onTemplateProductProduct(TemplateEvent $event)
    {
        $this->twigRenderService->initRenderService($event);

        /** @var Product $Product */
        $Product = $event->getParameter('Product');

        if ($Product->hasProductClass()) {
            // 規格あり商品
        } else {
            // 規格なし商品
            $this->twigRenderService
                ->insertBuilder()
                ->find('.c-primaryCol > div')
                ->eq(0)
                ->setTemplate('@PinpointSale/admin/Product/default/pinpoint_area.twig')
                ->setTargetId('plugin_pinpoint_block')
                ->setInsertModeAfter()
                ->setScript('@PinpointSale/admin/Product/default/pinpoint_area_js.twig');

            $this->twigRenderService->addSupportSnippet();

            // Style追加
            $event->addAsset('@PinpointSale/admin/Product/pinpoint_sale_product_css.twig');

            // カレンダー表示ができないブラウザ用
            $event->addAsset('@PinpointSale/admin/datetimepicker_asset.twig');
        }
    }

    /**
     * 商品　規格登録
     *
     * @param TemplateEvent $event
     */
    public function onTemplateProductClassEdit(TemplateEvent $event)
    {

        $this->twigRenderService->initRenderService($event);

        /** @var FormView $form */
        $form = $event->getParameter('form');

        // エラー状態の判定
        $formProductClasses = $form['product_classes'];

        $list = [];

        /** @var FormView $formRow */
        foreach ($formProductClasses as $formRow) {
            $this->formHelper->validList($list, $formRow);
        }

        $pinpointInputValidOther = true;
        $pinpointInputValid = true;

        // 入力エラー時の制御
        foreach ($list as $item) {

            if ($this->formHelper
                ->isParentName('productPinpoints', $item)) {
                $pinpointInputValid = false;
            } else {
                $pinpointInputValidOther = false;
            }
        }

        $event->setParameter('pinpointInputValidOther', $pinpointInputValidOther);
        $event->setParameter('pinpointInputValid', $pinpointInputValid);

        // 切り替えボタン
        $this->twigRenderService
            ->insertBuilder()
            ->find('form')
            ->find('.justify-content-between')
            ->find('div > span')
            ->eq(0)
            ->setTargetId('pinpoint_sale_change')
            ->setInsertModeAppend();

        if (!$pinpointInputValidOther
            && !$pinpointInputValid) {

            // エラーメッセージ
            $this->twigRenderService
                ->insertBuilder()
                ->find('form')
                ->find('.justify-content-between')
                ->find('div')
                ->eq(0)
                ->setTargetId('pinpoint_sale_msg')
                ->setInsertModeAppend();
        }

        // タイトル
        $this->twigRenderService
            ->insertBuilder()
            ->find('#ex-product_class > table > thead')
            ->eq(0)
            ->setTargetId('pinpoint_sale_thead')
            ->setInsertModeAfter();

        // リスト
        $eachChild = $this->twigRenderService->eachChildBuilder();
        $eachChild
            ->findAndDataKey('#ex-product_class-', 'pinpoint_sale_product_class_name')
            ->targetFindThis()
            ->setInsertModeAfter();

        $this->twigRenderService
            ->eachBuilder()
            ->find('.product_class_pinpoint_sale_target')
            ->each($eachChild->build());

        $this->twigRenderService->addSupportSnippet(
            '@PinpointSale/admin/Product/class/pinpoint_product_class.twig',
            '@PinpointSale/admin/Product/class/pinpoint_product_class_js.twig'
        );

        // Style追加
        $event->addAsset('@PinpointSale/admin/Product/pinpoint_sale_product_css.twig');

        // カレンダー表示ができないブラウザ用
        $event->addAsset('@PinpointSale/admin/datetimepicker_asset.twig');
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
            // 商品詳細
            '@admin/Product/product.twig' => ['onTemplateProductProduct', -10],
            // 規格登録
            '@admin/Product/product_class.twig' => ['onTemplateProductClassEdit'],
            // 商品一覧
            '@admin/Product/index.twig' => ['onTemplateProductIndex', 10],
        ];
    }
}
