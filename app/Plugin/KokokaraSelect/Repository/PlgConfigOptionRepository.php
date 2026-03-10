<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/06
 */

namespace Plugin\KokokaraSelect\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\KokokaraSelect\Entity\PlgConfigOption;

class PlgConfigOptionRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlgConfigOption::class);
    }
}
