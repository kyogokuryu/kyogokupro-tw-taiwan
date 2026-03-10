<?php

namespace Plugin\ECCUBE4LineIntegration\Repository;

use Eccube\Repository\AbstractRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Plugin\ECCUBE4LineIntegration\Entity\LineIntegrationHistory;

class LineIntegrationHistoryRepository extends AbstractRepository
{

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LineIntegrationHistory::class);
    }
}
