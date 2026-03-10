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

namespace Plugin\JaccsPayment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_jaccs_payment_history")
 * @ORM\Entity(repositoryClass="Plugin\JaccsPayment\Repository\HistoryRepository")
 */
class History extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Eccube\Entity\Order
     *
     * @ORM\ManyToOne(targetEntity="\Eccube\Entity\Order", inversedBy="Historys")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_id", type="string", length=1024, nullable=true)
     */
    private $transaction_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=1024, nullable=true)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var string
     *
     * @ORM\Column(name="item", type="text", nullable=true)
     */
    private $item;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\JaccsPayment\Entity\ReOrder", mappedBy="History")
     */
    private $JaccsReOrders;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Eccube\Entity\Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * @param \Eccube\Entity\Order $Order
     *
     * @return History
     */
    public function setOrder(\Eccube\Entity\Order $Order)
    {
        $this->Order = $Order;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @param string $transaction_id
     *
     * @return History
     */
    public function setTransactionId($transaction_id)
    {
        $this->transaction_id = $transaction_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return History
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param \DateTime $create_date
     *
     * @return History
     */
    public function setCreateDate(\DateTime $create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * @return string
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param string $item
     *
     * @return History
     */
    public function setItem($item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * @param ReOrder $reOrder
     *
     * @return $this
     */
    public function addJaccsReOrder(\Plugin\JaccsPayment\Entity\ReOrder $reOrder)
    {
        $this->JaccsReOrders[] = $reOrder;

        return $this;
    }

    /**
     * @param ReOrder $reOrder
     *
     * @return bool
     */
    public function removeJaccsReOrder(\Plugin\JaccsPayment\Entity\ReOrder $reOrder)
    {
        return $this->JaccsReOrders->removeElement($reOrder);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getJaccsReOrders()
    {
        return $this->JaccsReOrders;
    }
}
