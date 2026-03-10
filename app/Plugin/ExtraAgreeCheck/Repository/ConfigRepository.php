<?php

namespace Plugin\ExtraAgreeCheck\Repository;

use Plugin\ExtraAgreeCheck\Entity\Config;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ConfigRepository extends \Eccube\Repository\AbstractRepository
{
    /**
     * ConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @param int $id
     *
     * @return Config|null
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }
}
