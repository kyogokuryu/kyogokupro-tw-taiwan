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
use Eccube\Service\PurchaseFlow\PurchaseProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Annotation\OrderFlow;
use Eccube\Entity\Order;
use Plugin\BundleSale4\Entity\OrderBundleItem;
use Plugin\BundleSale4\Repository\OrderBundleItemRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @ShoppingFlow
 * @OrderFlow
 */
class BundleItemProcessor implements PurchaseProcessor
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var OrderBundleItemRepository
     */
    private $orderBundleItemRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        OrderBundleItemRepository $orderBundleItemRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->orderBundleItemRepository = $orderBundleItemRepository;
    }

    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {

    }

    public function prepare(ItemHolderInterface $target, PurchaseContext $context)
    {
        if (!$this->supports($target)) {
            return;
        }

        foreach ($target->getProductOrderItems() as $item) {
            foreach ($item->getProductClass()->getProduct()->getBundleItems() as $BundleItem) {
                $OrderBundleItem = $this->orderBundleItemRepository->findOneBy([
                    "OrderItem" => $item,
                    "ProductClass" => $BundleItem->getProductClass()
                ]);

                if (is_null($OrderBundleItem)) {
                    $OrderBundleItem = new OrderBundleItem();
                }

                $OrderBundleItem->setOrder($item->getOrder());
                $OrderBundleItem->setOrderItem($item);
                $OrderBundleItem->setQuantity($BundleItem->getQuantity());
                $OrderBundleItem->setProduct($BundleItem->getProduct());
                $OrderBundleItem->setProductClass($BundleItem->getProductClass());
                $this->entityManager->persist($OrderBundleItem);
            }
        }
    }

    public function rollback(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$this->supports($itemHolder)) {
            return;
        }
    }

    private function supports(ItemHolderInterface $itemHolder)
    {
        if (!$itemHolder instanceof Order) {
            return false;
        }

        return true;
    }

}
