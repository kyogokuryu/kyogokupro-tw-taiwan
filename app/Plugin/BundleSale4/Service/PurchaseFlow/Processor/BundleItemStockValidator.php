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

use Eccube\Annotation\CartFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\ItemValidator;

/**
 * @CartFlow
 */
class BundleItemStockValidator extends ItemValidator
{

    /**
     * @param ItemInterface $item
     * @param PurchaseContext $context
     *
     * @throws InvalidItemException
     */
    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isProduct()) {
            return;
        }

        foreach ($item->getProductClass()->getProduct()->getBundleItems() as $BundleItem) {
            if ($BundleItem->getProductClass()->isStockUnlimited()) {
                continue;
            }

            $stock = $BundleItem->getProductClass()->getStock();
            $quantity = $item->getQuantity() * $BundleItem->getQuantity();
            if ($stock == 0) {
                $this->throwInvalidItemException('front.shopping.out_of_stock_zero', $item->getProductClass());
            }
            if ($stock < $quantity) {
                $this->throwInvalidItemException('front.shopping.out_of_stock', $item->getProductClass());
            }
        }
    }

    protected function handle(ItemInterface $item, PurchaseContext $context)
    {
        foreach ($item->getProductClass()->getProduct()->getBundleItems() as $BundleItem) {
            $stock = $BundleItem->getProductClass()->getStock();
            $quantity = $item->getQuantity() * $BundleItem->getQuantity();

            if (($stock - $quantity) < 1) {
                $item->setQuantity(0);
                break;
            } else {
                $item->setQuantity($stock);
            }
        }
    }

}
