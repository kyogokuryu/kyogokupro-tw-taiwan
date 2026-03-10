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

namespace Plugin\BundleSale4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\BundleSale4\Entity\BundleItem;
use Doctrine\Common\Persistence\ManagerRegistry;

class BundleItemRepository extends AbstractRepository
{
    /**
     * BundleItemRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BundleItem::class);
    }

    public function countByProductClass(array $ProductClasses)
    {
        $qb = $this->createQueryBuilder("bi");
        return $qb
            ->select("count(bi)")
            ->where($qb->expr()->in("bi.ProductClass", ":ProductClasses"))
            ->setParameter("ProductClasses", $ProductClasses)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByProductClass(array $ProductClasses)
    {
        $qb = $this->createQueryBuilder("bi");
        return $qb
            ->where($qb->expr()->in("bi.ProductClass", ":ProductClasses"))
            ->setParameter("ProductClasses", $ProductClasses)
            ->getQuery()
            ->getResult();
    }
}
