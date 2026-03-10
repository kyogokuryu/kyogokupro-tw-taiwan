<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DroppedCartNotifierPlugin\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\DroppedCartNotifierPlugin\Entity\DroppedCartNotifierConfig;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DroppedCartNotifierConfigRepository extends AbstractRepository
{
    /**
     * DroppedCartNotifierConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DroppedCartNotifierConfig::class);
    }

    /**
     * @param int $id
     *
     * @return DroppedCartNotifierConfig
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }

    /**
     * 初期設定のデフォルトを生成
     *
     * @return DroppedCartNotifierConfig
     */
    public function createDefaultConfig()
    {
        $config = new DroppedCartNotifierConfig();
        $config->setId(1);
        $config->setPastDayToNotify(5);
        $config->setMaxCartItem(5);
        $config->setMaxRecommendedItem(5);
        $config->setMailSubject("お買い忘れはありませんか？");
        $config->setBaseUrl(""); // `url('homepage')`をtwigで設定
        $config->setIsSendReportMail(true);

        return $config;
    }
}
