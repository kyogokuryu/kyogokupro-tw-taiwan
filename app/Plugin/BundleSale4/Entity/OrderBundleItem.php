<?php
/**
 * This file is part of BundleSale4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\BundleSale4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;

/**
 * @author Akira Kurozumi <info@a-zumi.net>
 *
 * @ORM\Table(name="plg_bs4_order_bundle_item")
 * @ORM\Entity(repositoryClass="Plugin\BundleSale4\Repository\OrderBundleItemRepository")
 */
class OrderBundleItem {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=0, options={"default":0})
     */
    private $quantity = 0;

    /**
     * @var \Eccube\Entity\Order
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;

    /**
     * @var \Eccube\Entity\OrderItem
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\OrderItem", inversedBy="OrderBundleItems")
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id")
     */
    private $OrderItem;

    /**
     * @var \Eccube\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $Product;

    /**
     * @var \Eccube\Entity\ProductClass
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass")
     * @ORM\JoinColumn(name="product_class_id", referencedColumnName="id")
     */
    private $ProductClass;

    /**
     * Get recommend product id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set recommend product id.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set quantity.
     *
     * @param int $quantity
     *
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setOrder(Order $Order)
    {
        $this->Order = $Order;

        return $this;
    }

    public function getOrde()
    {
        return $this->Order;
    }

    public function setOrderItem(OrderItem $OrderItem)
    {
        $this->OrderItem = $OrderItem;

        return $this;
    }

    public function getOrderItem()
    {
        return $this->OrderItem;
    }

    public function setProduct(Product $Product)
    {
        $this->Product = $Product;

        return $this;
    }

    public function getProduct()
    {
        return $this->Product;
    }

    public function setProductClass(ProductClass $ProductClass)
    {
        $this->ProductClass = $ProductClass;

        return $this;
    }

    public function getProductClass()
    {
        return $this->ProductClass;
    }

}
