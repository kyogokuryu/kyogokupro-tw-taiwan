<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/23
 */

namespace Plugin\KokokaraSelect\Service;


use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Repository\KsCartSelectItemRepository;
use Plugin\KokokaraSelect\Repository\KsSelectItemRepository;

class KsSelectItemService
{

    /** @var KsSelectItemRepository */
    protected $ksSelectItemRepository;

    /** @var KsCartSelectItemRepository */
    protected $ksCartSelectItemRepository;

    public function __construct(
        KsSelectItemRepository $ksSelectItemRepository,
        KsCartSelectItemRepository $ksCartSelectItemRepository
    )
    {
        $this->ksSelectItemRepository = $ksSelectItemRepository;
        $this->ksCartSelectItemRepository = $ksCartSelectItemRepository;
    }

    /**
     * 元商品を加味した在庫無制限判定
     *
     * @param KsSelectItem $ksSelectItem
     * @return bool
     */
    public function isStockUnlimited(KsSelectItem $ksSelectItem)
    {
        if ($ksSelectItem->isStockUnlimited()
            && $ksSelectItem->getProductClass()->isStockUnlimited()) {
            return true;
        }

        return false;
    }

    /**
     * 元商品を加味した在庫数取得
     *
     * @param KsSelectItem $ksSelectItem
     * @param bool $ksSelectItemStockReal true:割当の入力数値 返却
     * @return int|null
     */
    public function getStock(KsSelectItem $ksSelectItem, $ksSelectItemStockReal = false)
    {
        if ($ksSelectItem->isStockUnlimited()) {
            // 商品の在庫数を利用
            $stock = (int)$ksSelectItem->getProductClass()->getStock();
        } else {
            // 割当在庫数を利用
            $stock = $ksSelectItem->getStock();

            if (!$ksSelectItemStockReal) {
                if (!$ksSelectItem->getProductClass()->isStockUnlimited()
                    && $stock > $ksSelectItem->getProductClass()->getStock()) {
                    // 本体側の在庫が割当を下回っている場合
                    $stock = (int)$ksSelectItem->getProductClass()->getStock();
                }
            }
        }

        return $stock;
    }

    /**
     * idからKsSelectItemを取得
     *
     * @param int $id
     * @return object|KsSelectItem|null
     */
    public function getKsSelectItemById($id)
    {
        if (!$id) {
            return null;
        }

        return $this->ksSelectItemRepository->find($id);
    }

    /**
     * KsSelectItemに関連するKsCartSelectItemをクリア
     *
     * @param $ksSelectItems
     */
    public function deleteRelationKsCartItem($ksSelectItems)
    {
        if (count($ksSelectItems) > 0) {
            $this->ksCartSelectItemRepository->deleteByKsSelectItem($ksSelectItems);
        }
    }

    /**
     * 選択商品の構成要素となっているか判定
     *
     * @param Product $product
     * @return bool true:構成要素 false:通常
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isSetting(Product $product)
    {

        if ($product && $product->getKsProductOption()) {
            if ($product->getKsProductOption()->isSelectOnly()) {
                return true;
            }
        }

        $targetProductClass = null;
        /** @var ProductClass $productClass */
        foreach ($product->getProductClasses() as $productClass) {
            $targetProductClass = $productClass;
            break;
        }

        return $this->ksSelectItemRepository->isKsProductClass($targetProductClass);
    }

    /**
     * 在庫数チェック
     *
     * @param KsSelectItem|integer $ksSelectItem
     * @param $targetQuantity
     * @return array
     */
    public function validStock($ksSelectItem, $targetQuantity)
    {

        if (!$ksSelectItem instanceof KsSelectItem) {
            $ksSelectItem = $this->getKsSelectItemById($ksSelectItem);
        }

        if ($this->isStockUnlimited($ksSelectItem)) {
            return [
                'result' => true,
                'stock' => null,
            ];
        }

        // 在庫数取得
        $stock = $this->getStock($ksSelectItem);

        if ($stock == 0) {
            // 在庫0
            return [
                'result' => false,
                'stock' => 0,
            ];
        }

        if ($stock < $targetQuantity) {
            return [
                'result' => false,
                'stock' => $stock,
            ];
        }

        return [
            'result' => true,
            'stock' => $stock,
        ];
    }

    /**
     * 選択商品商品名FULL取得
     *
     * @param ProductClass $productClass
     * @return string
     */
    public function getKsProductClassName(ProductClass $productClass)
    {
        $name = $productClass->getProduct()->getName();

        if ($productClass->getClassCategory1()) {
            $name .= '/' . $productClass->getClassCategory1()->getName();
        }

        if ($productClass->getClassCategory2()) {
            $name .= '/' . $productClass->getClassCategory2()->getName();
        }

        return $name;
    }
}
