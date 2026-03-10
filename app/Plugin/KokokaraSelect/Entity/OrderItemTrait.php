<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/10
 */

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\KokokaraSelect\Entity\KokokaraSelect\KsOrderItemRow;
use Plugin\KokokaraSelect\Entity\KokokaraSelect\KsOrderItemRows;
use Plugin\KokokaraSelect\Entity\KokokaraSelect\KsOrderItemStructure;

/**
 * Trait OrderItemTrait
 * @package Plugin\KokokaraSelect\Entity
 * @Eccube\EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{

    /**
     * @var KsOrderItemEx
     *
     * @ORM\OneToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsOrderItemEx", mappedBy="OrderItem", cascade={"persist", "remove"})
     */
    private $KsOrderItemEx;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\KokokaraSelect\Entity\KsOrderItemEx", mappedBy="ParentOrderItem")
     * @ORM\OrderBy({
     *   "groupId" = "ASC"
     * })
     */
    private $KsOrderItemChildren;

    /**
     * @var Product
     */
    private $ksTmpProduct;

    /**
     * @var ProductClass
     */
    private $ksTmpProductClass;

    /**
     * @var string
     */
    private $ksTmpProductName;

    /**
     * @return KsOrderItemEx
     */
    public function getKsOrderItemEx()
    {
        return $this->KsOrderItemEx;
    }

    /**
     * @param KsOrderItemEx $KsOrderItemEx
     * @return OrderItemTrait
     */
    public function setKsOrderItemEx(?KsOrderItemEx $KsOrderItemEx)
    {
        $this->KsOrderItemEx = $KsOrderItemEx;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getKsOrderItemChildren()
    {

        if (!$this->KsOrderItemChildren) {
            $this->KsOrderItemChildren = new ArrayCollection();
        }

        return $this->KsOrderItemChildren;
    }

    /**
     * @param Collection $KsOrderItemChildren
     * @return OrderItemTrait
     */
    public function setKsOrderItemChildren(Collection $KsOrderItemChildren)
    {
        $this->KsOrderItemChildren = $KsOrderItemChildren;
        return $this;
    }

    /**
     * @param KsOrderItemEx $ksOrderItemEx
     * @return $this
     */
    public function addKsOrderItemChildren(KsOrderItemEx $ksOrderItemEx)
    {
        if (!$this->KsOrderItemChildren) {
            $this->KsOrderItemChildren = new ArrayCollection();
        }

        $this->KsOrderItemChildren->add($ksOrderItemEx);

        return $this;
    }

    /**
     * @param KsOrderItemEx $ksOrderItemEx
     * @return bool
     */
    public function removeKsOrderItemChildren(KsOrderItemEx $ksOrderItemEx)
    {
        return $this->KsOrderItemChildren->removeElement($ksOrderItemEx);
    }

    /**
     * @return $this
     */
    public function clearKsOrderItemChildren()
    {

        if (!$this->KsOrderItemChildren) {
            $this->KsOrderItemChildren = new ArrayCollection();
        }

        $this->KsOrderItemChildren->clear();
        return $this;
    }

    /**
     * @return Product
     */
    public function getKsTmpProduct()
    {
        return $this->ksTmpProduct;
    }

    /**
     * @param Product $ksTmpProduct
     * @return OrderItemTrait
     */
    public function setKsTmpProduct(Product $ksTmpProduct)
    {
        $this->ksTmpProduct = $ksTmpProduct;
        return $this;
    }

    /**
     * @return ProductClass
     */
    public function getKsTmpProductClass()
    {
        return $this->ksTmpProductClass;
    }

    /**
     * @param ProductClass $ksTmpProductClass
     * @return OrderItemTrait
     */
    public function setKsTmpProductClass(ProductClass $ksTmpProductClass)
    {
        $this->ksTmpProductClass = $ksTmpProductClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getKsTmpProductName()
    {
        return $this->ksTmpProductName;
    }

    /**
     * @param string $ksTmpProductName
     * @return OrderItemTrait
     */
    public function setKsTmpProductName(string $ksTmpProductName)
    {
        $this->ksTmpProductName = $ksTmpProductName;
        return $this;
    }

    /**
     * 構成品か判定
     *
     * @return bool true:構成品
     */
    public function isKsSelectItem()
    {
        if ($this->getKsOrderItemEx()) {
            return true;
        }

        return false;
    }

    /**
     * @return KsProduct
     */
    public function getKsProduct()
    {
        /** @var ProductClass $productClass */
        $productClass = $this->getProductClass();
        $product = $productClass->getProduct();

        return $product->getKsProduct();
    }

    /**
     * 数量１単位にまとめた構成情報返却
     * @return KsOrderItemRows
     */
    public function getKsOrderItemRows()
    {
        $ksItemRows = new KsOrderItemRows($this);

        /** @var KsOrderItemEx $ksOrderItemChild */
        foreach ($this->getKsOrderItemChildren() as $ksOrderItemChild) {

            // 数量１単位の構成へ分解
            $groupIndex = $ksOrderItemChild->getGroupId();

            if ($ksOrderItemChild->getKsSelectItemGroup()) {
                $groupId = $ksOrderItemChild->getKsSelectItemGroup()->getId();
            } else {
                $groupId = $ksOrderItemChild->getKsSelectItemGroupId();
            }

            if ($ksOrderItemChild->getKsSelectItem()) {
                $ksSelectItemId = $ksOrderItemChild->getKsSelectItem()->getId();
            } else {
                $ksSelectItemId = $ksOrderItemChild->getKsSelectItemId();
            }

            $orderItem = $ksOrderItemChild->getOrderItem();

            if ($ksItemRows->isKsRow($groupIndex)) {
                $ksOrderItemRow = $ksItemRows->getKsOrderItemRowByGroupId($groupIndex);
            } else {
                $ksOrderItemRow = new KsOrderItemRow($groupIndex);
            }

            $ksOrderItemStructure = new KsOrderItemStructure();
            $ksOrderItemStructure
                ->setKsSelectItemGroupId($groupId)
                ->setKsSelectItemGroup($ksOrderItemChild->getKsSelectItemGroup())
                ->setKsSelectItemId($ksSelectItemId)
                ->setKsSelectItem($ksOrderItemChild->getKsSelectItem())
                ->setOrderItem($orderItem);

            $ksOrderItemRow->addKsOrderItemStructure(
                $ksSelectItemId,
                $ksOrderItemStructure
            );

            $ksItemRows->setKsOrderItemRows($ksOrderItemRow);
        }

        return $ksItemRows;
    }

}
