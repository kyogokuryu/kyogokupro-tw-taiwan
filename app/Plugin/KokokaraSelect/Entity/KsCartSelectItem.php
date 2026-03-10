<?php

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PlgKsCartSelectItem
 *
 * @ORM\Table(name="plg_ks_cart_select_item")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\KsCartSelectItemRepository")
 */
class KsCartSelectItem extends \Eccube\Entity\AbstractEntity
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
     * @var KsCartSelectItemGroup
     *
     * @ORM\ManyToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup", inversedBy="KsCartSelectItems")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="cart_select_item_group_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $KsCartSelectItemGroup;

    /**
     * @var KsSelectItem
     *
     * @ORM\ManyToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsSelectItem")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="select_item_id", referencedColumnName="id")
     * })
     */
    private $KsSelectItem;

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
     * Set quantity.
     *
     * @param string|null $quantity
     *
     * @return KsCartSelectItem
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
     * Set ksCartSelectItemGroup.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup|null $ksCartSelectItemGroup
     *
     * @return KsCartSelectItem
     */
    public function setKsCartSelectItemGroup(\Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup $ksCartSelectItemGroup = null)
    {
        $this->KsCartSelectItemGroup = $ksCartSelectItemGroup;

        return $this;
    }

    /**
     * Get ksCartSelectItemGroup.
     *
     * @return \Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup|null
     */
    public function getKsCartSelectItemGroup()
    {
        return $this->KsCartSelectItemGroup;
    }

    /**
     * Set ksSelectItem.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsSelectItem|null $ksSelectItem
     *
     * @return KsCartSelectItem
     */
    public function setKsSelectItem(\Plugin\KokokaraSelect\Entity\KsSelectItem $ksSelectItem = null)
    {
        $this->KsSelectItem = $ksSelectItem;

        return $this;
    }

    /**
     * Get ksSelectItem.
     *
     * @return \Plugin\KokokaraSelect\Entity\KsSelectItem|null
     */
    public function getKsSelectItem()
    {
        return $this->KsSelectItem;
    }
}
