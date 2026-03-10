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

use Doctrine\ORM\Query;
use Eccube\Repository\AbstractRepository;
use Plugin\JaccsPayment\Entity\PaymentStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class PaymentStatusRepository
 */
class PaymentStatusRepository extends AbstractRepository
{
    /**
     * PaymentStatusRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaymentStatus::class);
    }

    /**
     * find All Array
     *
     * @return array
     */
    public function findAllArray()
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT os FROM Plugin\JaccsPayment\Entity\PaymentStatus os INDEX BY os.id ORDER BY os.sort_no ASC')
        ;
        $result = $query
            ->getResult(Query::HYDRATE_ARRAY)
        ;

        return $result;
    }
}
