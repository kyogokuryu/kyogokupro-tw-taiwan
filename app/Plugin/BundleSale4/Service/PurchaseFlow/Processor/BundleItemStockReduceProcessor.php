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

use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Entity\ProductStock;
use Doctrine\DBAL\LockMode;
use Eccube\Exception\ShoppingException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Service\PurchaseFlow\PurchaseProcessor;

/**
 * @ShoppingFlow
 */
class BundleItemStockReduceProcessor implements PurchaseProcessor
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // 在庫を減らす
        $this->eachProductOrderItems($itemHolder, function ($currentStock, $itemQuantity) {
            return $currentStock - $itemQuantity;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // 在庫を戻す
        $this->eachProductOrderItems($itemHolder, function ($currentStock, $itemQuantity) {
            return $currentStock + $itemQuantity;
        });
    }

    private function eachProductOrderItems(ItemHolderInterface $itemHolder, callable $callback)
    {
        // Order以外の場合は何もしない
        if (!$itemHolder instanceof Order) {
            return;
        }

        foreach ($itemHolder->getProductOrderItems() as $item) {
            foreach ($item->getProductClass()->getProduct()->getBundleItems() as $BundleItem) {
                // 在庫が無制限かチェックし、制限ありなら在庫数をチェック
                if (!$BundleItem->getProductClass()->isStockUnlimited()) {
                    // 在庫チェックあり
                    /* @var ProductStock $productStock */
                    $productStock = $BundleItem->getProductClass()->getProductStock();
                    if($productStock->getProductClassId() === null) {
                        // 在庫に対してロックを実行
                        $this->entityManager->lock($productStock, LockMode::PESSIMISTIC_WRITE);
                        $this->entityManager->refresh($productStock);
                        $productStock->setProductClassId($BundleItem->getProductClass()->getId());
                    }
                    $ProductClass = $BundleItem->getProductClass();
                    $stock = $callback($productStock->getStock(), ($item->getQuantity() * $BundleItem->getQuantity()));
                    if ($stock < 0) {
                        throw new ShoppingException(trans('purchase_flow.over_stock', ['%name%' => $ProductClass->formattedProductName()]));
                    }
                    $productStock->setStock($stock);
                    $ProductClass->setStock($stock);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {
        // TODO: Implement commit() method.
    }
}
