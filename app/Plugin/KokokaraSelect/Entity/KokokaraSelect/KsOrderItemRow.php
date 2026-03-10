<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/14
 */

namespace Plugin\KokokaraSelect\Entity\KokokaraSelect;


class KsOrderItemRow
{

    private $groupIndex;

    private $ksOrderItemStructures;

    public function __construct($groupIndex)
    {
        $this->ksOrderItemStructures = [];
        $this->groupIndex = $groupIndex;
    }

    /**
     * 追加
     *
     * @param $ksSelectItemId
     * @param KsOrderItemStructure $ksOrderItemStructure
     */
    public function addKsOrderItemStructure($ksSelectItemId, KsOrderItemStructure $ksOrderItemStructure)
    {
        $this->ksOrderItemStructures[$ksSelectItemId][] = $ksOrderItemStructure;
    }

    /**
     * @return mixed
     */
    public function getGroupIndex()
    {
        return $this->groupIndex;
    }

    /**
     * @param mixed $groupIndex
     * @return KsOrderItemRow
     */
    public function setGroupIndex($groupIndex)
    {
        $this->groupIndex = $groupIndex;
        return $this;
    }

    /**
     * @return array
     */
    public function getKsOrderItemStructures()
    {
        return $this->ksOrderItemStructures;
    }

    /**
     * グループ単位の購入数量リスト返却
     *
     * @return array
     */
    public function getKsOrderSelectItemGroupQuantity()
    {
        $ksOrderSelectItemGroupQuantity = [];

        foreach ($this->ksOrderItemStructures as $values) {

            /** @var KsOrderItemStructure $ksOrderItemStructure */
            foreach ($values as $ksOrderItemStructure) {

                $ksSelectItemGroupId = $ksOrderItemStructure->getKsSelectItemGroupId();
                $orderItem = $ksOrderItemStructure->getOrderItem();

                if (isset($ksOrderSelectItemGroupQuantity[$ksSelectItemGroupId])) {
                    $ksOrderSelectItemGroupQuantity[$ksSelectItemGroupId] += $orderItem->getQuantity();
                } else {
                    $ksOrderSelectItemGroupQuantity[$ksSelectItemGroupId] = $orderItem->getQuantity();
                }
            }
        }

        return $ksOrderSelectItemGroupQuantity;
    }
}
