<?php

namespace Plugin\ECCUBE4LineIntegration\Repository;

use Eccube\Repository\AbstractRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Plugin\ECCUBE4LineIntegration\Entity\LineIntegrationSetting;

class LineIntegrationSettingRepository extends AbstractRepository
{

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LineIntegrationSetting::class);
    }
}
