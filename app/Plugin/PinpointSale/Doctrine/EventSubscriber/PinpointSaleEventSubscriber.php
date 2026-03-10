<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/17
 */

namespace Plugin\PinpointSale\Doctrine\EventSubscriber;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Eccube\Entity\ProductClass;
use Plugin\PinpointSale\Service\PinpointSaleHelper;
use Plugin\PinpointSale\Service\PinpointSaleService;

class PinpointSaleEventSubscriber implements EventSubscriber
{

    protected $pinpointSaleHelper;

    protected $pinpointSaleService;

    public function __construct(
        PinpointSaleHelper $pinpointSaleHelper,
        PinpointSaleService $pinpointSaleService
    )
    {
        $this->pinpointSaleHelper = $pinpointSaleHelper;
        $this->pinpointSaleService = $pinpointSaleService;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad,
            Events::preUpdate,
            Events::prePersist,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \Exception
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {

            /** @var ProductClass $productClass */
            $productClass = $entity;

            if ($this->pinpointSaleHelper->isAdminRoute()) {
                // 管理画面除外
                return;
            }

            if (!$this->pinpointSaleHelper->isHookRoute()) {
                // 価格フック対象外 (参照のみ可能とする）
                $entity->setPinpointSaleItem($this->pinpointSaleService->getPinpointSaleItem($entity));
                return;
            }

            // 現在の価格を待避
            $entity->setPinpointSaleOriginPrice02($entity->getPrice02());
            $entity->setPinpointSaleOriginPrice02IncTax($entity->getPrice02IncTax());

            // タイムセール設定からセール価格を取得
            $entity->setPinpointSaleItem($this->pinpointSaleService->getPinpointSaleItem($entity));

            $pinpointSalePrice02 = $this->pinpointSaleService->getPinpointSalePrice($productClass);
            $pinpointSalePrice02IncTax = $this->pinpointSaleService->getPinpointSalePriceIncTax($productClass);

            $entity->setPrice02($pinpointSalePrice02);
            $entity->setPrice02IncTax($pinpointSalePrice02IncTax);
        }

    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \Exception
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->resetPrice($args);
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \Exception
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->resetPrice($args);
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \Exception
     */
    private function resetPrice(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {

            if ($this->pinpointSaleHelper->isAdminRoute()) {
                // 管理画面除外
                return;
            }

            if (!$this->pinpointSaleHelper->isHookRoute()) {
                // 価格フック対象外
                return;
            }

            // 念のため価格情報をもとに戻す
            if (!empty($entity->getPinpointSaleOriginPrice02())) {
                $entity->setPrice02($entity->getPinpointSaleOriginPrice02());
            }

            // 念のため価格情報をもとに戻す
            if (!empty($entity->getPinpointSaleOriginPrice02IncTax())) {
                $entity->setPrice02IncTax($entity->getPinpointSaleOriginPrice02IncTax());
            }
        }
    }
}
