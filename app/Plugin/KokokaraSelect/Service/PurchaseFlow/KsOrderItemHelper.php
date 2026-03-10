<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/08/23
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow;


use Plugin\KokokaraSelect\Entity\KsProduct;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;

class KsOrderItemHelper
{

    use ConfigHelperTrait;

    /**
     * 選択商品の表示用名称取得
     *
     * @param KsProduct $parentKsProduct
     * @param $prefix
     * @param $parentName
     * @param $index
     * @param $productName
     * @return mixed
     */
    function getKsSelectOrderItemName(KsProduct $parentKsProduct, $prefix, $parentName, $index, $productName)
    {

        if($parentKsProduct->isDirectSelect()) {
            $headName = $this->getKokokaraSelectDirectSelectName();
        } else {
            $headName = $this->getKokokaraSelectName();
        }

        return trans('kokokara_select.order.item_name', [
            '%prefix%' => $prefix,
            '%kokokara_select%' => $headName,
            '%parent_name%' => $parentName,
            '%index%' => $index,
            '%product_name%' => $productName,
        ]);
    }
}
