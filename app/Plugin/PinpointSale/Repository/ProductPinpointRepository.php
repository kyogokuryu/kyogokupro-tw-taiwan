<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/09/01
 */

namespace Plugin\PinpointSale\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Entity\ProductClass;
use Eccube\Repository\AbstractRepository;
use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\ProductPinpoint;

class ProductPinpointRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductPinpoint::class);
    }

    /**
     * 商品規格IDを追加
     *
     * @param Pinpoint $pinpoint
     * @param ProductClass $productClass
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addProductClass($pinpoint, $productClass)
    {

        if (!$pinpoint->hasProductClass($productClass)) {

            $productPinpoint = new ProductPinpoint();
            $productPinpoint
                ->setPinpoint($pinpoint)
                ->setProductClass($productClass);

            $this->getEntityManager()->persist($productPinpoint);
            $this->getEntityManager()->flush();
        }

    }
}
