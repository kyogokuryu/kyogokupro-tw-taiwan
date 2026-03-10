<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/10
 */

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PlgKsProductOption
 *
 * @ORM\Table(name="plg_ks_product_option")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\KsProductOptionRepository")
 */
class KsProductOption extends \Eccube\Entity\AbstractEntity
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
     * @ORM\OneToOne(targetEntity="Eccube\Entity\Product", inversedBy="KsProductOption")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    /**
     * @var bool
     *
     * @ORM\Column(name="select_only", type="boolean", options={"default":false})
     */
    private $selectOnly;

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
     * Set selectOnly.
     *
     * @param bool $selectOnly
     *
     * @return KsProductOption
     */
    public function setSelectOnly($selectOnly)
    {
        $this->selectOnly = $selectOnly;

        return $this;
    }

    /**
     * Get selectOnly.
     *
     * @return bool
     */
    public function isSelectOnly()
    {
        return $this->selectOnly;
    }

    /**
     * Set product.
     *
     * @param \Eccube\Entity\Product|null $product
     *
     * @return KsProductOption
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
}
