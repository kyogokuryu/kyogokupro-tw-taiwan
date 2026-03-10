<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/08/23
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow\Processor;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Eccube\Annotation\OrderFlow;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\OrderItem;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Entity\KsOrderItemEx;
use Plugin\KokokaraSelect\Service\KsOrderService;

/**
 * @ShoppingFlow
 * @OrderFlow
 *
 * Class KokokaraSelectOrderItemHolderPreprocessor
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow\Processor
 */
class KokokaraSelectOrderItemHolderPreprocessor extends ItemValidator
{

    /** @var KsOrderService */
    protected $ksOrderService;

    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(
        KsOrderService $ksOrderService,
        EntityManagerInterface $entityManager
    )
    {
        $this->ksOrderService = $ksOrderService;
        $this->entityManager = $entityManager;
    }

    protected function validate(ItemInterface $item, PurchaseContext $context)
    {

        if (!$item instanceof OrderItem) {
            return;
        }

        // 選択商品の場合
        if ($item->isKsSelectItem()) {
            $parentOrderItem = $item->getKsOrderItemEx()->getParentOrderItem();
            $status = $this->entityManager->getUnitOfWork()->getEntityState($parentOrderItem);

            if ($status == UnitOfWork::STATE_REMOVED) {

                // 親 OrderItemが削除対象の場合自分自身も削除する
                $item->getOrder()->removeOrderItem($item);
                $item->getShipping()->removeOrderItem($item);

                $this->entityManager->remove($item);
            }

            return;
        }

        if ($item->isProduct()
            && $item->getKsProduct()
            && $item->getKsProduct()->isDirectSelect()) {

            $groupCount = 0;

            /** @var KsOrderItemEx $ksOrderItemChild */
            foreach ($item->getKsOrderItemChildren() as $ksOrderItemChild) {
                if ($groupCount < $ksOrderItemChild->getGroupId()) {
                    $groupCount = $ksOrderItemChild->getGroupId();
                }
            }

            if ($item->getQuantity() == $groupCount) {
                // 数量一致
                return;
            }
            $diff = $item->getQuantity() - $groupCount;


            if ($diff > 0) {
                // 固定選択商品生成
                $admin = $context->isOrderFlow();
                $this->ksOrderService->addKsOrderItemDirectSelect($item, $diff, $groupCount, $admin);
            } else {

                $min = $groupCount - abs($diff);

                // 削除
                /** @var KsOrderItemEx $ksOrderItemChild */
                foreach ($item->getKsOrderItemChildren() as $ksOrderItemChild) {

                    if ($ksOrderItemChild->getGroupId() > $min) {

                        $item->removeKsOrderItemChildren($ksOrderItemChild);
                        $item->getShipping()->removeOrderItem($ksOrderItemChild->getOrderItem());
                        $item->getOrder()->removeOrderItem($ksOrderItemChild->getOrderItem());

                        $this->entityManager->remove($ksOrderItemChild->getOrderItem());
                        $this->entityManager->remove($ksOrderItemChild);

                    }
                }
            }
        }

    }

}
