<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/07/12
 */

namespace Plugin\KokokaraSelect\Doctrine\EventListener;


use Eccube\Entity\ProductClass;
use Eccube\Request\Context;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Service\KsSelectItemService;
use Plugin\KokokaraSelect\Service\KsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductClassEventListener
{

    /** @var ContainerInterface */
    protected $container;

    /** @var KsService */
    protected $ksService;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var Context */
    protected $context;

    public function __construct(
        ContainerInterface $container,
        KsService $ksService,
        KsSelectItemService $ksSelectItemService,
        Context $context
    )
    {
        $this->container = $container;
        $this->ksService = $ksService;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->context = $context;
    }

    public function postLoad(ProductClass $productClass)
    {

        /** @var Request $request */
        $request = $this->container->get('request_stack')->getMasterRequest();
        if($request == null){return;}
        $route = $request->attributes->get('_route');

        if ($this->context->isAdmin()) {
            return;
        }

        if ($this->ksService->isDirectSelectKsProduct($productClass)) {
            // 固定セット商品の場合
            // 構成品に応じた在庫数に調整
            $ksProduct = $productClass->getProduct()->getKsProduct();

            $maxStock = null;

            /** @var KsSelectItemGroup $ksSelectItemGroup */
            foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {

                /** @var KsSelectItem $ksSelectItem */
                foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {
                    // 何点分確保できるか
                    if ($this->ksSelectItemService->isStockUnlimited($ksSelectItem)) {
                        // 在庫無制限なし
                        break;
                    }

                    $itemStock = $this->ksSelectItemService->getStock($ksSelectItem);
                    $groupStock = (int)($itemStock / $ksSelectItem->getQuantity());

                    if (is_null($maxStock) || $maxStock > $groupStock) {
                        // 確保可能数を設定
                        $maxStock = $groupStock;
                    }
                }
            }

            if (!is_null($maxStock)) {

                // Stockバックアップ
                $productClass
                    ->setKsOriginStock($productClass->getStock())
                    ->setKsOriginStockUnlimited($productClass->isStockUnlimited());

                // セット商品不足による在庫値変更
                if ($productClass->isStockUnlimited()) {

                    $productClass
                        ->setStock($maxStock)
                        ->setStockUnlimited(false);

                } else {

                    if ($productClass->getStock() > $maxStock) {
                        $productClass
                            ->setStock($maxStock)
                            ->setStockUnlimited(false);
                    }
                }
            }

        } else if ($route === 'product_list'
            || $route === 'kokokara_select_list'
            || $route === 'kokokara_select_list_edit') {

            $product = $productClass->getProduct();

            // 選択商品の場合構成要素の選択可能数量より在庫数を判別
            if ($this->ksService->isKsProduct($product)) {

                $ksProduct = $product->getKsProduct();
                /** @var KsSelectItemGroup $ksSelectItemGroup */
                foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {

                    $result = false;
                    $targetQuantity = (integer)$ksSelectItemGroup->getQuantity();
                    $stock = 0;
                    /** @var KsSelectItem $ksSelectItem */
                    foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {

                        if ($this->ksSelectItemService->isStockUnlimited($ksSelectItem)) {
                            // 在庫無制限なし
                            $result = true;
                            break;
                        }
                        $stock += $this->ksSelectItemService->getStock($ksSelectItem);

                        if ($targetQuantity <= $stock) {
                            $result = true;
                            break;
                        }
                    }

                    if (!$result) {

                        // Stockバックアップ
                        $productClass
                            ->setKsOriginStock($productClass->getStock())
                            ->setKsOriginStockUnlimited($productClass->isStockUnlimited());

                        // 数量不足により選択不可
                        $productClass
                            ->setStockUnlimited(false)
                            ->setStock(0);
                    }
                }
            }

        }

    }

    public function preUpdate(ProductClass $productClass)
    {

        /** @var Request $request */
        $request = $this->container->get('request_stack')->getMasterRequest();
        if($request){
            $route = $request->attributes->get('_route');
        }else{
            return;
        }

        if ($this->context->isAdmin()) {
            return;
        }

        if ($this->ksService->isDirectSelectKsProduct($productClass)) {

            if ($route === 'shopping_checkout') {
                // 在庫無制限の場合は戻す
                if ($productClass->isKsOriginStockUnlimited()) {
                    $this->restoreStock($productClass);
                }
            } else {
                $this->restoreStock($productClass);
            }

        } else if ($route === 'product_list'
            || $route === 'kokokara_select_list'
            || $route === 'kokokara_select_list_edit') {

            $this->restoreStock($productClass);
        }
    }

    /**
     * バックアップから在庫情報を戻す
     *
     * @param ProductClass $productClass
     */
    private function restoreStock(ProductClass $productClass)
    {

        // 更新前にStockのバックアップを戻す
        if ($productClass->getKsOriginStock() || $productClass->isKsOriginStockUnlimited()) {

            $productClass
                ->setStock($productClass->getKsOriginStock())
                ->setStockUnlimited($productClass->isKsOriginStockUnlimited());
        }
    }
}
