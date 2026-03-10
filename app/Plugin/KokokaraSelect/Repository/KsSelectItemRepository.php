<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Entity\ProductClass;
use Eccube\Repository\AbstractRepository;
use Plugin\KokokaraSelect\Entity\KsSelectItem;

class KsSelectItemRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KsSelectItem::class);
    }

    /**
     * 指定した商品規格が設定されたレコードがあるか判定
     *
     * @param ProductClass $productClass
     * @return bool true:あり false:なし
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isKsProductClass(ProductClass $productClass)
    {

        if (!$productClass) {
            return false;
        }

        if (empty($productClass->getId())) {
            return false;
        }

        $qb = $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.ProductClass = :productClass')
            ->setParameter('productClass', $productClass);

        $result = $qb->getQuery()->getSingleScalarResult();

        return ($result == 0 ? false : true);
    }
}
