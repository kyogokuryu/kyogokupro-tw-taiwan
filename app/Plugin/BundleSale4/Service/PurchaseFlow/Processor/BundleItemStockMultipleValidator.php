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

use Eccube\Entity\ItemHolderInterface;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Repository\ProductClassRepository;

/**
 * @ShoppingFlow
 */
class BundleItemStockMultipleValidator extends ItemHolderValidator
{

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * StockProcessor constructor.
     *
     * @param ProductClassRepository $productClassRepository
     */
    public function __construct(ProductClassRepository $productClassRepository)
    {
        $this->productClassRepository = $productClassRepository;
    }

    public function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        $OrderBundleItemsByProductClass = [];
        /** @var Shipping $Shipping */
        foreach ($itemHolder->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $Item) {
                if ($Item->isProduct()) {
                    foreach ($Item->getProductClass()->getProduct()->getBundleItems() as $BundleItem) {
                        $id = $BundleItem->getProductClass()->getId();
                        $OrderBundleItemsByProductClass[$id][] = [
                            "Item" => $Item,
                            "BundleItem" => $BundleItem
                        ];
                    }
                }
            }
        }

        foreach ($OrderBundleItemsByProductClass as $id => $BundleItems) {
            /** @var ProductClass $ProductClass */
            $ProductClass = $this->productClassRepository->find($id);
            if ($ProductClass->isStockUnlimited()) {
                continue;
            }
            $stock = $ProductClass->getStock();

            if ($stock == 0) {
                foreach ($BundleItems as $BundleItem) {
                    $BundleItem["Item"]->setQuantity(0);
                }
                $this->throwInvalidItemException('front.shopping.out_of_stock_zero', $ProductClass, true);
            }
            $isOver = false;
            foreach ($BundleItems as $BundleItem) {
                if ($stock - ($BundleItem["Item"]->getQuantity() * $BundleItem["BundleItem"]->getQuantity()) >= 0) {
                    $stock = $stock - ($BundleItem["Item"]->getQuantity() * $BundleItem["BundleItem"]->getQuantity());
                } else {
                    $BundleItem["Item"]->setQuantity(0);
                    $stock = 0;
                    $isOver = true;
                }
            }
            if ($isOver) {
                $this->throwInvalidItemException('front.shopping.out_of_stock', $ProductClass, true);
            }
        }
    }

}
