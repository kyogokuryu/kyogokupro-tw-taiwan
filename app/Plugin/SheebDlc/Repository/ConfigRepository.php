<?php

/*
 * Project Name: ダウンロードコンテンツ販売 プラグイン for 4.0
 * Copyright(c) 2019 Kenji Nakanishi. All Rights Reserved.
 *
 * https://www.facebook.com/web.kenji.nakanishi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\SheebDlc\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\SheebDlc\Entity\Config;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @package Plugin\SheebDlc\Entity
 */
class ConfigRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @param int $id
     * @return null|Config
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }
}
