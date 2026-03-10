<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/07/07
 */

namespace Plugin\KokokaraSelect\Doctrine\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Plugin\KokokaraSelect\Entity\KsOrderItemEx;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Repository\KsSelectItemGroupRepository;
use Plugin\KokokaraSelect\Repository\KsSelectItemRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class KsOrderItemExEventListener
{

    /** @var ContainerInterface */
    protected $container;

    /** @var KsSelectItemGroupRepository */
    protected $ksSelectItemGroupRepository;

    /** @var KsSelectItemRepository */
    protected $ksSelectItemRepository;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager
    )
    {
        $this->container = $container;
        $this->ksSelectItemGroupRepository = $entityManager->getRepository(KsSelectItemGroup::class);
        $this->ksSelectItemRepository = $entityManager->getRepository(KsSelectItem::class);
    }

    /**
     * @param KsOrderItemEx $ksOrderItemEx
     */
    public function postLoad(KsOrderItemEx $ksOrderItemEx)
    {

        $ksSelectItemGroupId = $ksOrderItemEx->getKsSelectItemGroupId();
        $ksSelectItemGroup = null;
        if ($ksSelectItemGroupId) {
            /** @var KsSelectItemGroup $ksSelectItemGroup */
            $ksSelectItemGroup = $this->ksSelectItemGroupRepository->find($ksSelectItemGroupId);
            $ksOrderItemEx->setKsSelectItemGroup($ksSelectItemGroup);
        }

        $ksSelectItemId = $ksOrderItemEx->getKsSelectItemId();
        if (!$ksSelectItemId) {
            return;
        }

        /** @var KsSelectItem $ksSelectItem */
        $targetKsSelectItem = $this->ksSelectItemRepository->find($ksSelectItemId);
        if (!$targetKsSelectItem && $ksSelectItemGroup) {
            // 復元実施
            $productClass = $ksOrderItemEx->getOrderItem()->getProductClass();
            if (!$productClass) {
                // Tempを探す
                $productClass = $ksOrderItemEx->getOrderItem()->getKsTmpProductClass();
            }
            if (!$productClass) {
                return;
            }

            // 選択商品と同じProductClassを選択商品として返す
            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {

                if ($ksSelectItem->getProductClass()->getId() == $productClass->getId()) {
                    $targetKsSelectItem = $ksSelectItem;
                    break;
                }
            }
        }
        $ksOrderItemEx->setKsSelectItem($targetKsSelectItem);
    }
}
