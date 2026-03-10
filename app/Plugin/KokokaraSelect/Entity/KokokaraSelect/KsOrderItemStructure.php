<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/14
 */

namespace Plugin\KokokaraSelect\Entity\KokokaraSelect;


use Eccube\Entity\OrderItem;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;

class KsOrderItemStructure
{

    private $ksSelectItemGroupId;

    private $ksSelectItemGroup;

    private $ksSelectItemId;

    private $ksSelectItem;

    private $orderItem;

    /**
     * @return mixed
     */
    public function getKsSelectItemGroupId()
    {
        return $this->ksSelectItemGroupId;
    }

    /**
     * @param mixed $ksSelectItemGroupId
     * @return KsOrderItemStructure
     */
    public function setKsSelectItemGroupId($ksSelectItemGroupId)
    {
        $this->ksSelectItemGroupId = $ksSelectItemGroupId;
        return $this;
    }

    public function setKsSelectItemId($ksSelectItemId)
    {
        $this->ksSelectItemId = $ksSelectItemId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getKsSelectItemId()
    {
        return $this->ksSelectItemId;
    }

    /**
     * @return OrderItem
     */
    public function getOrderItem()
    {
        return $this->orderItem;
    }

    /**
     * @param mixed $orderItem
     * @return KsOrderItemStructure
     */
    public function setOrderItem($orderItem)
    {
        $this->orderItem = $orderItem;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getKsSelectItemGroup()
    {
        return $this->ksSelectItemGroup;
    }

    /**
     * @param KsSelectItemGroup $ksSelectItemGroup
     * @return KsOrderItemStructure
     */
    public function setKsSelectItemGroup($ksSelectItemGroup)
    {
        $this->ksSelectItemGroup = $ksSelectItemGroup;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getKsSelectItem()
    {
        return $this->ksSelectItem;
    }

    /**
     * @param KsSelectItem $ksSelectItem
     * @return KsOrderItemStructure
     */
    public function setKsSelectItem($ksSelectItem)
    {
        $this->ksSelectItem = $ksSelectItem;
        return $this;
    }

}
