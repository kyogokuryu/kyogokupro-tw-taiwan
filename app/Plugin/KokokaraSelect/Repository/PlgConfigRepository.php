<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/06
 */

namespace Plugin\KokokaraSelect\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Plugin\KokokaraSelect\Entity\PlgConfig;
use Plugin\KokokaraSelect\Entity\PlgConfigOption;
use Plugin\KokokaraSelect\Service\PlgConfigService\Repository\AbstractConfigRepository;

class PlgConfigRepository extends AbstractConfigRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry,
            PlgConfig::class,
            PlgConfigOption::class);
    }
}
