<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/10
 */

namespace Plugin\KokokaraSelect\EventSubscriber;


use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\KsSelectItemService;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\TwigRenderService\EventSubscriber\TwigRenderTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminProductEventSubscriber implements EventSubscriberInterface
{

    use TwigRenderTrait;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var KsService */
    protected $ksService;

    /** @var KsOrderService */
    protected $ksOrderService;

    public function __construct(
        KsSelectItemService $ksSelectItemService,
        KsService $ksService,
        KsOrderService $ksOrderService
    )
    {
        $this->ksSelectItemService = $ksSelectItemService;
        $this->ksService = $ksService;
        $this->ksOrderService = $ksOrderService;
    }

    /**
     * 商品編集テンプレート
     *
     * @param TemplateEvent $event
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onTemplateProductProduct(TemplateEvent $event)
    {
        $this->initRenderService($event);

        // エリア追加
        $this->createInsertBuilder()
            ->find('.c-primaryCol > div')
            ->eq(0)
            ->setTargetId('kokokara_select_block')
            ->setInsertModeAfter();

        /** @var Product $product */
        $product = $event->getParameter('Product');

        // 構成要素判定
        $ksSelectItemSetting = false;
        if ($product) {

            // 規格が設定された商品は、選択商品設定を表示しない
            if ($product->hasProductClass()) {
                return;
            }

            $ksSelectItemSetting = $this->ksSelectItemService->isSetting($product);
        }
        $event->setParameter('KsSelectItemSetting', $ksSelectItemSetting);

        // 販売済み判定
        if (!$this->ksService->isKsProduct($product)
            && $this->ksOrderService->isBuyIngProduct($product)) {
            $ksSelectItemBuyIng = true;
        } else {
            $ksSelectItemBuyIng = false;
        }
        $event->setParameter('KsSelectItemBuyIng', $ksSelectItemBuyIng);

        $this->addTwigRenderSnippet(
            '@KokokaraSelect/admin/Product/edit/kokokara_select_area.twig'
        );
    }

    /**
     * 商品一覧テンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateProductIndex(TemplateEvent $event)
    {
        $this->initRenderService($event);

        // 選択商品対象表示
        $child = $this->twigRenderService
            ->eachChildBuilder()
            ->findAndDataKey('#ex-product-', 'kokokara_select_product_id')
            ->find('td')
            ->eq(3)
            ->targetFindThis()
            ->setInsertModeAppend();

        $this->twigRenderService
            ->eachBuilder()
            ->find('.kokokara_select_info')
            ->each($child->build());

        $this->twigRenderService->addSupportSnippet(
            '@KokokaraSelect/admin/Product/index/kokokara_select_info.twig',
            null, true
        );
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // 商品登録
            '@admin/Product/product.twig' => ['onTemplateProductProduct'],
            // 商品一覧
          '@admin/Product/index.twig' => ['onTemplateProductIndex', 10],
        ];
    }
}
