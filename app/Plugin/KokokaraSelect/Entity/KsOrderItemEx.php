<?php

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\OrderItem;

/**
 * PlgKsOrderItemEx
 *
 * @ORM\Table(name="plg_ks_order_item_ex")
 * @ORM\Entity
 */
class KsOrderItemEx extends \Eccube\Entity\AbstractEntity
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
     * @var OrderItem
     *
     * @ORM\OneToOne(targetEntity="Eccube\Entity\OrderItem", inversedBy="KsOrderItemEx")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_item_id", referencedColumnName="id")
     * })
     */
    private $OrderItem;

    /**
     * @var OrderItem
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\OrderItem", inversedBy="KsOrderItemChildren")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_order_item_id", referencedColumnName="id")
     * })
     */
    private $ParentOrderItem;

    /**
     * @var int|null
     *
     * @ORM\Column(name="group_id", type="integer", nullable=true)
     */
    private $groupId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="ks_select_item_id", type="integer", nullable=true)
     */
    private $ksSelectItemId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="ks_select_item_group_id", type="integer", nullable=true)
     */
    private $ksSelectItemGroupId;

    /**
     * @var string
     *
     * @ORM\Column(name="product_name", type="string", length=255)
     */
    private $productName;

    /**
     * @var KsSelectItemGroup
     */
    private $ksSelectItemGroup = null;

    /**
     * @var KsSelectItem
     */
    private $ksSelectItem = null;

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
     * Set groupId.
     *
     * @param int|null $groupId
     *
     * @return KsOrderItemEx
     */
    public function setGroupId($groupId = null)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int|null
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @return OrderItem
     */
    public function getOrderItem()
    {
        return $this->OrderItem;
    }

    /**
     * @param OrderItem $OrderItem
     * @return KsOrderItemEx
     */
    public function setOrderItem(OrderItem $OrderItem)
    {
        $this->OrderItem = $OrderItem;
        return $this;
    }

    /**
     * Set parentOrderItem.
     *
     * @param \Eccube\Entity\OrderItem $parentOrderItem
     *
     * @return KsOrderItemEx
     */
    public function setParentOrderItem(\Eccube\Entity\OrderItem $parentOrderItem)
    {
        $this->ParentOrderItem = $parentOrderItem;

        return $this;
    }

    /**
     * Get parentOrderItem.
     *
     * @return \Eccube\Entity\OrderItem|null
     */
    public function getParentOrderItem()
    {
        return $this->ParentOrderItem;
    }

    /**
     * @return int|null
     */
    public function getKsSelectItemId()
    {
        return $this->ksSelectItemId;
    }

    /**
     * @param int|null $ksSelectItemId
     * @return KsOrderItemEx
     */
    public function setKsSelectItemId(?int $ksSelectItemId)
    {
        $this->ksSelectItemId = $ksSelectItemId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getKsSelectItemGroupId()
    {
        return $this->ksSelectItemGroupId;
    }

    /**
     * @param int|null $ksSelectItemGroupId
     * @return KsOrderItemEx
     */
    public function setKsSelectItemGroupId(?int $ksSelectItemGroupId)
    {
        $this->ksSelectItemGroupId = $ksSelectItemGroupId;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param string $productName
     * @return KsOrderItemEx
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;
        return $this;
    }

    /**
     * @return KsSelectItemGroup
     */
    public function getKsSelectItemGroup()
    {
        return $this->ksSelectItemGroup;
    }

    /**
     * @param KsSelectItemGroup|null $ksSelectItemGroup
     * @return KsOrderItemEx
     */
    public function setKsSelectItemGroup(?KsSelectItemGroup $ksSelectItemGroup)
    {
        $this->ksSelectItemGroup = $ksSelectItemGroup;
        return $this;
    }

    /**
     * @return KsSelectItem
     */
    public function getKsSelectItem()
    {
        return $this->ksSelectItem;
    }

    /**
     * @param KsSelectItem $ksSelectItem
     * @return KsOrderItemEx
     */
    public function setKsSelectItem(?KsSelectItem $ksSelectItem)
    {
        $this->ksSelectItem = $ksSelectItem;
        return $this;
    }

}
