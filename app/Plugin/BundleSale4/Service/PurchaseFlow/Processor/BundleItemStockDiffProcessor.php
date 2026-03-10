<?php
/**
 * This file is part of BundleSale4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\BundleSale4\Service\PurchaseFlow\Processor;

use Eccube\Annotation\OrderFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Service\PurchaseFlow\Processor\StockDiffProcessor;


/**
 * @OrderFlow
 */
class BundleItemStockDiffProcessor extends StockDiffProcessor
{

    protected function getQuantityByProductClass(ItemHolderInterface $ItemHolder)
    {
        $ItemsByProductClass = [];
        foreach ($ItemHolder->getItems() as $Item) {
            if ($Item->isProduct()) {
                foreach ($Item->getProductClass()->getProduct()->getBundleItems() as $BundleItem) {
                    $id = $BundleItem->getProductClass()->getId();
                    if (isset($ItemsByProductClass[$id])) {
                        $ItemsByProductClass[$id] += ($Item->getQuantity() * $BundleItem->getQuantity());
                    } else {
                        $ItemsByProductClass[$id] = ($Item->getQuantity() * $BundleItem->getQuantity());
                    }
                }
            }
        }

        return $ItemsByProductClass;
    }
}
