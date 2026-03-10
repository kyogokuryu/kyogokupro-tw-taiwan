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

use Eccube\Repository\AbstractRepository;
use Plugin\JaccsPayment\Entity\Config;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class ConfigRepository
 */
class ConfigRepository extends AbstractRepository
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
     * @return null|object
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }
}
