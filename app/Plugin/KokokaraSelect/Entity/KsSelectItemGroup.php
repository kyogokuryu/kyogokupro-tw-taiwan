<?php

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * PlgKsSelectItemGroup
 *
 * @ORM\Table(name="plg_ks_select_item_group")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\KsSelectItemGroupRepository")
 */
class KsSelectItemGroup extends \Eccube\Entity\AbstractEntity
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
     * @var KsProduct
     *
     * @ORM\ManyToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsProduct", inversedBy="ksSelectItemGroups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ks_product_id", referencedColumnName="id")
     * })
     */
    private $KsProduct;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\KokokaraSelect\Entity\KsSelectItem", mappedBy="KsSelectItemGroup", cascade={"persist", "remove"})
     * @ORM\OrderBy({
     *     "sortNo" = "ASC"
     * })
     */
    private $KsSelectItems;

    /**
     * @var string|null
     *
     * @ORM\Column(name="group_name", type="string", length=255, nullable=true)
     */
    private $groupName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=4000, nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $quantity;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="smallint", options={"unsigned":true})
     */
    private $sortNo;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->KsSelectItems = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set groupName.
     *
     * @param string|null $groupName
     *
     * @return KsSelectItemGroup
     */
    public function setGroupName($groupName = null)
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * Get groupName.
     *
     * @return string|null
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return KsSelectItemGroup
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
     * @return string|null
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param string|null $quantity
     * @return KsSelectItemGroup
     */
    public function setQuantity(?string $quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Set ksProduct.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsProduct|null $ksProduct
     *
     * @return KsSelectItemGroup
     */
    public function setKsProduct(\Plugin\KokokaraSelect\Entity\KsProduct $ksProduct = null)
    {
        $this->KsProduct = $ksProduct;

        return $this;
    }

    /**
     * Get ksProduct.
     *
     * @return \Plugin\KokokaraSelect\Entity\KsProduct|null
     */
    public function getKsProduct()
    {
        return $this->KsProduct;
    }

    /**
     * Add ksSelectItem.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsSelectItem $ksSelectItem
     *
     * @return KsSelectItemGroup
     */
    public function addKsSelectItem(\Plugin\KokokaraSelect\Entity\KsSelectItem $ksSelectItem)
    {
        $this->KsSelectItems[] = $ksSelectItem;

        return $this;
    }

    /**
     * Remove ksSelectItem.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsSelectItem $ksSelectItem
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeKsSelectItem(\Plugin\KokokaraSelect\Entity\KsSelectItem $ksSelectItem)
    {
        return $this->KsSelectItems->removeElement($ksSelectItem);
    }

    /**
     * Get ksSelectItems.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getKsSelectItems()
    {
        return $this->KsSelectItems;
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
     * @return KsSelectItemGroup
     */
    public function setSortNo(int $sortNo)
    {
        $this->sortNo = $sortNo;
        return $this;
    }

}
