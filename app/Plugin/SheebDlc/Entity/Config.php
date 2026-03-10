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

namespace Plugin\SheebDlc\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(name="plg_sheeb_dlc_config")
 * @ORM\Entity(repositoryClass="Plugin\SheebDlc\Repository\ConfigRepository")
 */
class Config extends AbstractEntity
{
    const MODE_LOCAL = 1;
    const MODE_GOOGLE_DRIVE = 2;
    
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     */
    private $id;
    
    /**
     * @ORM\Column(name="available_order_status", type="text", nullable=false)
     */
    private $available_order_status;
    
    /**
     * @ORM\Column(name="mode", type="integer", nullable=false)
     */
    private $mode;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAvailableOrderStatus()
    {
        return $this->available_order_status;
    }

    /**
     * @param $available_order_status
     * @return $this
     */
    public function setAvailableOrderStatus($available_order_status)
    {
        $this->available_order_status = $available_order_status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param $mode
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }
}
