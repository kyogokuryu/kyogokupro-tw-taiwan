<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/03
 */

namespace Plugin\PinpointSale\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\PinpointSale\Entity\PinpointRepeat;

class PinpointRepeatRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PinpointRepeat::class);
    }
}
