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
use Eccube\Annotation\EntityExtension;
use Eccube\Entity\Master\OrderStatus;
use Plugin\JaccsPayment\Service\Method\JaccsPayment;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\JaccsPayment\Entity\History", mappedBy="Order")
     */
    private $Historys;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\JaccsPayment\Entity\ReOrder", mappedBy="Order")
     */
    private $JaccsReOrders;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\JaccsPayment\Entity\ShippingRequest", mappedBy="Order")
     */
    private $JaccsShippingRequests;

    /**
     * 決済ステータスを保持するカラム.
     *
     * dtb_order.jaccs_payment_payment_status_id
     *
     * @var JaccsPaymentPaymentStatus
     * @ORM\ManyToOne(targetEntity="Plugin\JaccsPayment\Entity\PaymentStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jaccs_payment_payment_status_id", referencedColumnName="id")
     * })
     */
    private $JaccsPaymentPaymentStatus;

    /**
     * @param \Plugin\JaccsPayment\Entity\History $history
     *
     * @return $this
     */
    public function addHistory(\Plugin\JaccsPayment\Entity\History $history)
    {
        $this->Historys[] = $history;

        return $this;
    }

    /**
     * @param \Plugin\JaccsPayment\Entity\History $history
     *
     * @return bool
     */
    public function removeHistory(\Plugin\JaccsPayment\Entity\History $history)
    {
        return $this->Historys->removeElement($history);
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getHistorys()
    {
        return $this->Historys;
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

    /**
     * @param ShippingRequest $shippingRequest
     * @return $this
     */
    public function addJaccsShippingRequest(\Plugin\JaccsPayment\Entity\ShippingRequest $shippingRequest)
    {
        $this->JaccsShippingRequests[] = $shippingRequest;

        return $this;
    }

    /**
     * @param ShippingRequest $shippingRequest
     * @return bool
     */
    public function removeJaccsShippingRequest(\Plugin\JaccsPayment\Entity\ShippingRequest $shippingRequest)
    {
        return $this->JaccsShippingRequests->removeElement($shippingRequest);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getJaccsShippingRequests()
    {
        return $this->JaccsShippingRequests;
    }

    /**
     * @return JaccsPaymentPaymentStatus
     */
    public function getJaccsPaymentPaymentStatus()
    {
        return $this->JaccsPaymentPaymentStatus;
    }

    /**
     * @param JaccsPaymentPaymentStatus $JaccsPaymentPaymentStatus|null
     */
    public function setJaccsPaymentPaymentStatus(PaymentStatus $JaccsPaymentPaymentStatus = null)
    {
        $this->JaccsPaymentPaymentStatus = $JaccsPaymentPaymentStatus;
    }

    /**
     * @return bool
     */
    public function isJaccsPayment()
    {
        return $this->getPayment() ? ($this->getPayment()->getMethodClass() == JaccsPayment::class) : false;
    }

    /**
     * @return string
     */
    public function getCustomerJacccsStatus()
    {
        if ($this->isJaccsPayment() && $this->getOrderStatus() && $this->getCustomerOrderStatus()) {
            if ($this->getOrderStatus()->getId() == OrderStatus::NEW && $this->getJaccsPaymentPaymentStatus()) {
                if ($this->getJaccsPaymentPaymentStatus()->getId() == PaymentStatus::JACCS_ORDER_PRE_END) {
                    return '注⽂受付';
                } else {
                    return '注⽂未完了';
                }
            } else {
                return $this->getCustomerOrderStatus()->getName();
            }
        }

        return '';
    }
}
