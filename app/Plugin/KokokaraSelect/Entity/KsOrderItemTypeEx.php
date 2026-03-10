<?php

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PlgOrderItemTypeEx
 *
 * @ORM\Table(name="plg_ks_order_item_type_ex")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\KsOrderItemTypeExRepository")
 */
class KsOrderItemTypeEx extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="order_item_type_id", type="integer")
     */
    private $orderItemTypeId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="order_item_type_sort_no", type="integer")
     */
    private $orderItemTypeSortNo;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="kokokara_select", type="boolean", nullable=true)
     */
    private $kokokaraSelect = false;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set orderItemTypeId.
     *
     * @param int $orderItemTypeId
     *
     * @return KsOrderItemTypeEx
     */
    public function setOrderItemTypeId($orderItemTypeId)
    {
        $this->orderItemTypeId = $orderItemTypeId;

        return $this;
    }

    /**
     * Get orderItemTypeId.
     *
     * @return int
     */
    public function getOrderItemTypeId()
    {
        return $this->orderItemTypeId;
    }

    /**
     * Set orderItemTypeSortNo.
     *
     * @param int $orderItemTypeSortNo
     *
     * @return KsOrderItemTypeEx
     */
    public function setOrderItemTypeSortNo($orderItemTypeSortNo)
    {
        $this->orderItemTypeSortNo = $orderItemTypeSortNo;

        return $this;
    }

    /**
     * Get orderItemTypeSortNo.
     *
     * @return int
     */
    public function getOrderItemTypeSortNo()
    {
        return $this->orderItemTypeSortNo;
    }

    /**
     * Set kokokaraSelect.
     *
     * @param bool|null $kokokaraSelect
     *
     * @return KsOrderItemTypeEx
     */
    public function setKokokaraSelect($kokokaraSelect = null)
    {
        $this->kokokaraSelect = $kokokaraSelect;

        return $this;
    }

    /**
     * Get kokokaraSelect.
     *
     * @return bool|null
     */
    public function getKokokaraSelect()
    {
        return $this->kokokaraSelect;
    }
}
