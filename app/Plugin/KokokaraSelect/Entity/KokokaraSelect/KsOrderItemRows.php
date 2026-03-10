<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/14
 */

namespace Plugin\KokokaraSelect\Entity\KokokaraSelect;


use Eccube\Entity\OrderItem;

class KsOrderItemRows
{

    /** @var OrderItem */
    private $targetOrderItem;

    /** @var array */
    private $ksOrderItemRows;

    public function __construct($targetOrderItem)
    {
        $this->targetOrderItem = $targetOrderItem;
        $this->ksOrderItemRows = [];
    }

    /**
     * 存在チェック
     *
     * @param $groupId
     * @return bool
     */
    public function isKsRow($groupId)
    {
        if (isset($this->ksOrderItemRows[$groupId])) {
            return true;
        }

        return false;
    }

    /**
     * @param $groupIndex
     * @return KsOrderItemRow
     */
    public function getKsOrderItemRowByGroupId($groupIndex)
    {
        return $this->ksOrderItemRows[$groupIndex];
    }

    /**
     * @return array
     */
    public function getKsOrderItemRow()
    {
        return $this->ksOrderItemRows;
    }

    /**
     * @param KsOrderItemRow $ksOrderItemRow
     * @return $this
     */
    public function setKsOrderItemRows(KsOrderItemRow $ksOrderItemRow)
    {
        $this->ksOrderItemRows[$ksOrderItemRow->getGroupIndex()] = $ksOrderItemRow;
        return $this;
    }

    /**
     * @return array
     */
    public function getKsOrderItemGroups()
    {
        $result = [];
        /** @var KsOrderItemRow $ksItemRow */
        foreach ($this->ksOrderItemRows as $ksItemRow) {
            foreach ($ksItemRow->getKsOrderSelectItemGroupQuantity() as $key => $item) {
                $result[$key] = true;
            }
        }

        return $result;
    }

    /**
     * @return \Eccube\Entity\ProductClass|null
     */
    public function getProductClass()
    {
        return $this->targetOrderItem->getProductClass();
    }

}
