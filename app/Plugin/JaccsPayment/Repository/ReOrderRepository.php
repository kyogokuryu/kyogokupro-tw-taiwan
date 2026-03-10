<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JaccsPayment\Repository;

use Eccube\Entity\Order;
use Eccube\Repository\AbstractRepository;
use Plugin\JaccsPayment\Entity\History;
use Plugin\JaccsPayment\Entity\ReOrder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class ReOrderRepository
 */
class ReOrderRepository extends AbstractRepository
{
    /**
     * ReOrderRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReOrder::class);
    }

    /**
     * @param Order $order
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delReEditOrder(Order $order)
    {
        $items = $this->findBy(['Order' => $order]);
        if (count($items)) {
            /** @var $item ReOrder */
            foreach ($items as $item) {
                $this->getEntityManager()->remove($item);
                $item->getHistory()->removeJaccsReOrder($item);
            }
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param Order $order
     * @param History $history
     * @param $type
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addReEditOrder(Order $order, History $history, $type)
    {
        $reOrder = new ReOrder();
        $reOrder->setType($type);
        $reOrder->setCreateDate(new \DateTime('now'));

        $reOrder->setOrder($order);
        $order->addJaccsReOrder($reOrder);

        $reOrder->setHistory($history);
        $history->addJaccsReOrder($reOrder);

        $this->getEntityManager()->persist($reOrder);
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->persist($history);

        $this->getEntityManager()->flush();
    }

    /**
     * @param Order $order
     *
     * @return null|ReOrder
     */
    public function getReEditData(Order $order)
    {
        return $this->findOneBy(['Order' => $order]);
    }
}
