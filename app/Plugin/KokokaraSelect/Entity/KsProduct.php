<?php

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\ProductStatus;

/**
 * PlgKsProduct
 *
 * @ORM\Table(name="plg_ks_product")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\KsProductRepository")
 */
class KsProduct extends \Eccube\Entity\AbstractEntity
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
     * @var $Product
     *
     * @ORM\OneToOne(targetEntity="Eccube\Entity\Product", inversedBy="KsProduct")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    /**
     * @var string|null
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $quantity;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=4000, nullable=true)
     */
    private $description;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\KokokaraSelect\Entity\KsSelectItemGroup", mappedBy="KsProduct", cascade={"persist", "remove"})
     * @ORM\OrderBy({
     *     "sortNo" = "ASC"
     * })
     */
    private $KsSelectItemGroups;

    /**
     * @var bool
     *
     * @ORM\Column(name="price_view", type="boolean", options={"default":false})
     */
    private $priceView = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="direct_select", type="boolean", options={"default":false})
     */
    private $directSelect = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->KsSelectItemGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set quantity.
     *
     * @param string|null $quantity
     *
     * @return KsProduct
     */
    public function setQuantity($quantity = null)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return string|null
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return KsProduct
     */
    public function setDescription($description = null)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set product.
     *
     * @param \Eccube\Entity\Product|null $product
     *
     * @return KsProduct
     */
    public function setProduct(\Eccube\Entity\Product $product = null)
    {
        $this->Product = $product;

        return $this;
    }

    /**
     * Get product.
     *
     * @return \Eccube\Entity\Product|null
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * Add ksSelectItemGroup.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsSelectItemGroup $ksSelectItemGroup
     *
     * @return KsProduct
     */
    public function addKsSelectItemGroup(\Plugin\KokokaraSelect\Entity\KsSelectItemGroup $ksSelectItemGroup)
    {
        $this->KsSelectItemGroups[] = $ksSelectItemGroup;

        return $this;
    }

    /**
     * Remove ksSelectItemGroup.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsSelectItemGroup $ksSelectItemGroup
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeKsSelectItemGroup(\Plugin\KokokaraSelect\Entity\KsSelectItemGroup $ksSelectItemGroup)
    {
        return $this->KsSelectItemGroups->removeElement($ksSelectItemGroup);
    }

    /**
     * Get ksSelectItemGroups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getKsSelectItemGroups()
    {
        return $this->KsSelectItemGroups;
    }

    /**
     * @return bool
     */
    public function isPriceView()
    {
        return $this->priceView;
    }

    /**
     * @param bool $priceView
     * @return KsProduct
     */
    public function setPriceView(bool $priceView)
    {
        $this->priceView = $priceView;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDirectSelect()
    {
        return $this->directSelect;
    }

    /**
     * @param bool $directSelect
     * @return KsProduct
     */
    public function setDirectSelect(bool $directSelect)
    {
        $this->directSelect = $directSelect;
        return $this;
    }

    /**
     * Get KsSelectItemGroup
     *
     * @param $sortNo
     * @return KsSelectItemGroup|null
     */
    public function getKsSelectItemGroupBySortNo($sortNo)
    {
        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($this->getKsSelectItemGroups() as $ksSelectItemGroup) {
            if ($ksSelectItemGroup->getSortNo() == $sortNo) {
                return $ksSelectItemGroup;
            }
        }
        return null;
    }

    /**
     * KeyをIDとした選択商品のグループリスト返却
     *
     * @return array
     */
    public function getKsSelectItemGroupsAndKey()
    {
        $ksSelectItemGroups = [];
        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($this->getKsSelectItemGroups() as $ksSelectItemGroup) {

            $skip = true;
            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {
                if ($ksSelectItem->getProductClass()->getProduct()->getStatus()->getId() == ProductStatus::DISPLAY_SHOW) {
                    $skip = false;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            $ksSelectItemGroups[$ksSelectItemGroup->getId()] = $ksSelectItemGroup;
        }

        return $ksSelectItemGroups;
    }

    /**
     * KeyをIDとした選択商品の構成要素リスト返却
     *
     * @return array
     */
    public function getKsSelectItemsAndKey()
    {
        $ksSelectItems = [];
        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($this->getKsSelectItemGroups() as $ksSelectItemGroup) {
            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {
                $ksSelectItems[$ksSelectItem->getId()] = $ksSelectItem;
            }
        }

        return $ksSelectItems;
    }
}
