<?php

namespace Plugin\JaccsPayment\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\JaccsPayment\Entity\ShippingRequest;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ShippingRequestRepository extends AbstractRepository
{
    /**
     * HistoryRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ShippingRequest::class);
    }
}