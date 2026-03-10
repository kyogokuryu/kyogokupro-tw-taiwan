<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MypageReceipt2\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * MypageReceipt2Config
 *
 * @ORM\Table(name="plg_mypage_receipt2_config")
 * @ORM\Entity(repositoryClass="Plugin\MypageReceipt2\Repository\MypageReceipt2ConfigRepository")
 */
class MypageReceipt2Config extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="mypage_receipt2_enable", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $mypage_receipt2_enable;

	/**
	 * @var \Eccube\Entity\Master\OrderStatus
	 *
	 * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\OrderStatus")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="mypage_receipt2_status", referencedColumnName="id")
	 * })
	 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
	 */
	private $OrderStatus;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMypageReceipt2Enable()
    {
        return $this->mypage_receipt2_enable;
    }

    /**
     * @param int $mypage_receipt2_enable
     */
    public function setMypageReceipt2Enable($mypage_receipt2_enable)
    {
        $this->mypage_receipt2_enable = $mypage_receipt2_enable;
    }

	/**
	 * Set orderStatus.
	 *
	 * @param \Eccube\Entity\Master\OrderStatus|null $orderStatus
	 */
	public function setOrderStatus(\Eccube\Entity\Master\OrderStatus $orderStatus = null)
	{
		$this->OrderStatus = $orderStatus;
	
		return $this;
	}
	/**
	 * Get orderStatus.
	 *
	 * @return \Eccube\Entity\Master\OrderStatus|null
	 */
	public function getOrderStatus()
	{
		return $this->OrderStatus;
	}

}
