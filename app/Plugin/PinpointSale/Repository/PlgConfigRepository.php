<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/07/13
 */

namespace Plugin\PinpointSale\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Plugin\PinpointSale\Entity\PlgConfig;
use Plugin\PinpointSale\Entity\PlgConfigOption;
use Plugin\PinpointSale\Service\PlgConfigService\Repository\AbstractConfigRepository;

class PlgConfigRepository extends AbstractConfigRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry,
            PlgConfig::class,
            PlgConfigOption::class
        );
    }
}
