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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\ResultSetMapping;
use Eccube\Entity\Order;
use Eccube\Repository\AbstractRepository;
use Plugin\JaccsPayment\Entity\History;
use Symfony\Bridge\Doctrine\RegistryInterface;


use Plugin\JaccsPayment\Lib\Xml\Modifytransaction;
use Plugin\JaccsPayment\Lib\Xml\Transaction;
use Plugin\JaccsPayment\Lib\Xml\Shippingrequest;
use Plugin\JaccsPayment\Lib\Xml\Getauthori;

/**
 * Class HistoryRepository
 */
class HistoryRepository extends AbstractRepository
{
    /**
     * HistoryRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, History::class);
    }

    /**
     * @param $orders
     *
     * @return array
     */
    public function getOrderTransactionIds($orders)
    {
        $orderIds = [];
        foreach ($orders as $order) {
            $orderIds[] = $order->getId();
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('transaction_id', 'transaction_id');
        $rsm->addScalarResult('order_id', 'order_id');

        $ids = $this->getEntityManager()->createNativeQuery('
        SELECT transaction_id, order_id FROM plg_jaccs_payment_history WHERE EXISTS (
            SELECT 
                id
            FROM
                (
                    SELECT 
                        MAX(id) id
                    FROM 
                        plg_jaccs_payment_history 
                    WHERE 
                        transaction_id IS NOT NULL AND order_id IN (:orderIds)    
                    GROUP BY order_id
                ) table_id
            WHERE id=plg_jaccs_payment_history.id
        )', $rsm)
            ->setParameter('orderIds', $orderIds)
            ->getResult();

        $reData = [];

        if (count($ids)) {
            foreach ($ids as $data) {
                $reData[$data['order_id']] = $data['transaction_id'];
            }
        }

        return $reData;
    }

    /**
     * @param Order $order
     * @return History
     */
    public function getReHistory(Order $order)
    {

        $qb = $this->createQueryBuilder('h');
        $result = $qb->select('h')
            ->where('h.Order = :order')
            ->andWhere($qb->expr()->in('h.type', [
                Modifytransaction\Response::class,
                Transaction\Response::class,
                Getauthori\Response::class,
            ]))
            ->andWhere('h.transaction_id IS NOT NULL')
            ->orderBy('h.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('order', $order)
            ->getQuery()
            ->getResult();

        if (count($result)) {
            return $result[0];
        }

        return null;
    }
}
