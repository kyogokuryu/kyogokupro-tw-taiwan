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
 * @ORM\Table(name="plg_jaccs_re_order")
 * @ORM\Entity(repositoryClass="Plugin\JaccsPayment\Repository\ReOrderRepository")
 */
class ReOrder extends \Eccube\Entity\AbstractEntity
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
     * @ORM\ManyToOne(targetEntity="\Eccube\Entity\Order", inversedBy="JaccsReOrders")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;

    /**
     * @var \Plugin\JaccsPayment\Entity\History
     *
     * @ORM\ManyToOne(targetEntity="\Plugin\JaccsPayment\Entity\History", inversedBy="JaccsReOrders")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="history_id", referencedColumnName="id")
     * })
     */
    private $History;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=true)
     */
    protected $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

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
     * @return ReOrder
     */
    public function setOrder(\Eccube\Entity\Order $Order)
    {
        $this->Order = $Order;

        return $this;
    }

    /**
     * @return History
     */
    public function getHistory()
    {
        return $this->History;
    }

    /**
     * @param History $History
     *
     * @return ReOrder
     */
    public function setHistory(History $History)
    {
        $this->History = $History;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return ReOrder
     */
    public function setType(int $type)
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
     * @return ReOrder
     */
    public function setCreateDate(\DateTime $create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }
}
