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
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;

/**
 * @ORM\Table(name="plg_bs4_bundle_item")
 * @ORM\Entity(repositoryClass="Plugin\BundleSale4\Repository\BundleItemRepository")
 */
class BundleItem {

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
    private $quantity = 1;

    /**
     * @var \Eccube\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product", inversedBy="BundleItems")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $Product;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass")
     * @ORM\JoinColumn(name="product_class_id", referencedColumnName="id", nullable=false)
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

    /**
     * Set Product.
     *
     * @param \Eccube\Entity\Product $Product
     *
     * @return $this
     */
    public function setProduct(Product $Product)
    {
        $this->Product = $Product;

        return $this;
    }

    /**
     * Get Product.
     *
     * @return \Eccube\Entity\Product|null
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * getProductClass.
     *
     * @return ProductClass
     */
    public function getProductClass()
    {
        return $this->ProductClass;
    }

    /**
     * setProductClass.
     *
     * @param ProductClass $ProductClass
     *
     * @return $this
     */
    public function setProductClass(ProductClass $ProductClass = null)
    {
        $this->ProductClass = $ProductClass;

        return $this;
    }

}
