<?php

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\ProductClass;

/**
 * PlgKsSelectItem
 *
 * @ORM\Table(name="plg_ks_select_item")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\KsSelectItemRepository")
 */
class KsSelectItem extends \Eccube\Entity\AbstractEntity
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
     * @var KsSelectItemGroup
     *
     * @ORM\ManyToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsSelectItemGroup", inversedBy="KsSelectItems")
     */
    private $KsSelectItemGroup;

    /**
     * @var KsSelectItemStock
     *
     * @ORM\OneToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsSelectItemStock", mappedBy="KsSelectItem", cascade={"persist", "remove"})
     */
    private $KsSelectItemStock;

    /**
     * @var ProductClass
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass", inversedBy="KsSelectItems")
     */
    private $ProductClass;

    /**
     * @var int|null
     *
     * @ORM\Column(name="stock", type="integer", nullable=true)
     */
    private $stock;

    /**
     * @var boolean
     *
     * @ORM\Column(name="stock_unlimited", type="boolean", options={"default":false})
     */
    private $stockUnlimited = false;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="smallint", options={"unsigned":true})
     */
    private $sortNo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $quantity;

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
     * Set stock.
     *
     * @param int|null $stock
     *
     * @return KsSelectItem
     */
    public function setStock($stock = null)
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * Get stock.
     *
     * @return int|null
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * Set stockUnlimited.
     *
     * @param bool|null $stockUnlimited
     *
     * @return KsSelectItem
     */
    public function setStockUnlimited($stockUnlimited = null)
    {
        $this->stockUnlimited = $stockUnlimited;

        return $this;
    }

    /**
     * Get stockUnlimited.
     *
     * @return boolean
     */
    public function isStockUnlimited()
    {
        return $this->stockUnlimited;
    }

    /**
     * Set ksSelectItemGroup.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsSelectItemGroup|null $ksSelectItemGroup
     *
     * @return KsSelectItem
     */
    public function setKsSelectItemGroup(\Plugin\KokokaraSelect\Entity\KsSelectItemGroup $ksSelectItemGroup = null)
    {
        $this->KsSelectItemGroup = $ksSelectItemGroup;

        return $this;
    }

    /**
     * Get ksSelectItemGroup.
     *
     * @return \Plugin\KokokaraSelect\Entity\KsSelectItemGroup|null
     */
    public function getKsSelectItemGroup()
    {
        return $this->KsSelectItemGroup;
    }

    /**
     * Set ksSelectItemStock.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsSelectItemStock|null $ksSelectItemStock
     *
     * @return KsSelectItem
     */
    public function setKsSelectItemStock(\Plugin\KokokaraSelect\Entity\KsSelectItemStock $ksSelectItemStock = null)
    {
        $this->KsSelectItemStock = $ksSelectItemStock;

        return $this;
    }

    /**
     * Get ksSelectItemStock.
     *
     * @return \Plugin\KokokaraSelect\Entity\KsSelectItemStock|null
     */
    public function getKsSelectItemStock()
    {
        return $this->KsSelectItemStock;
    }

    /**
     * @return ProductClass
     */
    public function getProductClass()
    {
        return $this->ProductClass;
    }

    /**
     * @param ProductClass $ProductClass
     * @return KsSelectItem
     */
    public function setProductClass(ProductClass $ProductClass)
    {
        $this->ProductClass = $ProductClass;
        return $this;
    }

    /**
     * @return int
     */
    public function getSortNo()
    {
        return $this->sortNo;
    }

    /**
     * @param int $sortNo
     * @return KsSelectItem
     */
    public function setSortNo(int $sortNo)
    {
        $this->sortNo = $sortNo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param string|null $quantity
     * @return KsSelectItem
     */
    public function setQuantity(?string $quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

}
