<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\KokokaraSelect\Entity\KsProduct;

class KsProductRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KsProduct::class);
    }

    public function getQueryBuilderByProductSearch($productId)
    {
        return $this->createQueryBuilder('kp')
            ->join('kp.Product', 'p')
            ->andWhere('p.id = :productId')
            ->setParameter('productId', $productId);

    }
}
