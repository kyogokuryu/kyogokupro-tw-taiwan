<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/10
 */

namespace Plugin\KokokaraSelect\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\KokokaraSelect\Entity\KsProductOption;

class KsProductOptionRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KsProductOption::class);
    }

    public function getExistsSubQueryBuilder()
    {
        return $this->createQueryBuilder('kspo')
            ->andWhere('kspo.Product = p.id')
            ->andWhere('kspo.selectOnly = true');
    }
}
