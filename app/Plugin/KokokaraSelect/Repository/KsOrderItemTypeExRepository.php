<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/09
 */

namespace Plugin\KokokaraSelect\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\KokokaraSelect\Entity\KsOrderItemTypeEx;

class KsOrderItemTypeExRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KsOrderItemTypeEx::class);
    }

    public function get()
    {
        $results = $this->findAll();

        // データは１件しかない前提

        /** @var KsOrderItemTypeEx $result */
        foreach ($results as $result) {
            return $result;
        }
    }
}
