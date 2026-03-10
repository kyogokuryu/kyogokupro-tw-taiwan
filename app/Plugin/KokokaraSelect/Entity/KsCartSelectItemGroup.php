<?php

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\CartItem;

/**
 * PlgKsCartSelectItemGroup
 *
 * @ORM\Table(name="plg_ks_cart_select_item_group")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\KsCartSelectItemGroupRepository")
 */
class KsCartSelectItemGroup extends \Eccube\Entity\AbstractEntity
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
     * @var CartItem
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\CartItem", inversedBy="KsCartSelectItemGroups")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="cart_item_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $CartItem;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\KokokaraSelect\Entity\KsCartSelectItem", mappedBy="KsCartSelectItemGroup", cascade={"persist", "remove"})
     */
    private $KsCartSelectItems;

    /**
     * @var string
     *
     * @ORM\Column(name="cart_key", type="string", length=255)
     */
    private $cartKey;

    /**
     * @var string
     *
     * @ORM\Column(name="ks_cart_key", type="string", length=255)
     */
    private $ksCartKey;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->KsCartSelectItems = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set cartItem.
     *
     * @param \Eccube\Entity\CartItem|null $cartItem
     *
     * @return KsCartSelectItemGroup
     */
    public function setCartItem(\Eccube\Entity\CartItem $cartItem = null)
    {
        $this->CartItem = $cartItem;

        return $this;
    }

    /**
     * Get cartItem.
     *
     * @return \Eccube\Entity\CartItem|null
     */
    public function getCartItem()
    {
        return $this->CartItem;
    }

    /**
     * Add ksCartSelectItem.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsCartSelectItem $ksCartSelectItem
     *
     * @return KsCartSelectItemGroup
     */
    public function addKsCartSelectItem(\Plugin\KokokaraSelect\Entity\KsCartSelectItem $ksCartSelectItem)
    {
        $this->KsCartSelectItems[] = $ksCartSelectItem;

        return $this;
    }

    /**
     * Remove ksCartSelectItem.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsCartSelectItem $ksCartSelectItem
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeKsCartSelectItem(\Plugin\KokokaraSelect\Entity\KsCartSelectItem $ksCartSelectItem)
    {
        return $this->KsCartSelectItems->removeElement($ksCartSelectItem);
    }

    /**
     * @return $this
     */
    public function clearKsCartSelectItem()
    {
        $this->KsCartSelectItems->clear();
        return $this;
    }

    /**
     * Get ksCartSelectItems.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getKsCartSelectItems()
    {
        return $this->KsCartSelectItems;
    }

    /**
     * @return string
     */
    public function getCartKey()
    {
        return $this->cartKey;
    }

    /**
     * @param string $cartKey
     * @return KsCartSelectItemGroup
     */
    public function setCartKey(string $cartKey)
    {
        $this->cartKey = $cartKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getKsCartKey()
    {
        return $this->ksCartKey;
    }

    /**
     * @param string $ksCartKey
     * @return KsCartSelectItemGroup
     */
    public function setKsCartKey(string $ksCartKey)
    {
        $this->ksCartKey = $ksCartKey;
        return $this;
    }

}
