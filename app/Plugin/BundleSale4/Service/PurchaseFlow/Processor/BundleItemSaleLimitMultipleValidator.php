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
use Eccube\Repository\ProductClassRepository;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Annotation\ShoppingFlow;

/**
 * @ShoppingFlow
 */
class BundleItemSaleLimitMultipleValidator extends ItemHolderValidator
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

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     *
     * @throws \Eccube\Service\PurchaseFlow\InvalidItemException
     */
    public function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        $OrderBundleItemsByProductClass = [];
        foreach ($itemHolder->getItems() as $Item) {
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

        foreach ($OrderBundleItemsByProductClass as $id => $BundleItems) {
            $ProductClass = $this->productClassRepository->find($id);
            $limit = $ProductClass->getSaleLimit();
            if (null === $limit) {
                continue;
            }
            $isOver = false;
            foreach ($BundleItems as $BundleItem) {
                if (($limit - ($BundleItem["Item"]->getQuantity() * $BundleItem["BundleItem"]->getQuantity())) >= 0) {
                    $limit = $limit - ($BundleItem["Item"]->getQuantity() * $BundleItem["BundleItem"]->getQuantity());
                } else {
                    $BundleItem["Item"]->setQuantity(0);
                    $limit = 0;
                    $isOver = true;
                }
            }
            if ($isOver) {
                $this->throwInvalidItemException('front.shopping.over_sale_limit', $ProductClass, true);
            }
        }
    }

}
