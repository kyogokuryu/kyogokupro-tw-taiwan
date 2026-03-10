<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/02
 */

namespace Plugin\KokokaraSelect\Service;


use Eccube\Entity\Product;

class KsProductService
{

    /**
     * 選択商品か判定
     *
     * @param Product $product
     * @return bool true:選択商品, false:通常商品
     */
    public function isKsProduct(Product $product)
    {
        $ksProduct = $product->getKsProduct();

        if ($ksProduct) {
            return true;
        }

        return false;
    }


}
