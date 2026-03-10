<?php

namespace Plugin\PinpointSale\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductPinpoint
 *
 * @ORM\Table(name="plg_product_pinpoint")
 * @ORM\Entity(repositoryClass="Plugin\PinpointSale\Repository\ProductPinpointRepository")
 */
class ProductPinpoint extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Plugin\PinpointSale\Entity\Pinpoint
     *
     * @ORM\ManyToOne(targetEntity="Plugin\PinpointSale\Entity\Pinpoint", inversedBy="ProductPinpoints", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pinpoint_id", referencedColumnName="id")
     * })
     */
    private $Pinpoint;

    /**
     * @var \Eccube\Entity\ProductClass
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass", inversedBy="ProductPinpoints")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_class_id", referencedColumnName="id")
     * })
     */
    private $ProductClass;

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
     * Set pinpoint.
     *
     * @param \Plugin\PinpointSale\Entity\Pinpoint|null $pinpoint
     *
     * @return ProductPinpoint
     */
    public function setPinpoint(\Plugin\PinpointSale\Entity\Pinpoint $pinpoint = null)
    {
        $this->Pinpoint = $pinpoint;

        return $this;
    }

    /**
     * Get pinpoint.
     *
     * @return \Plugin\PinpointSale\Entity\Pinpoint|null
     */
    public function getPinpoint()
    {
        return $this->Pinpoint;
    }

    /**
     * Set productClass.
     *
     * @param \Eccube\Entity\ProductClass|null $productClass
     *
     * @return ProductPinpoint
     */
    public function setProductClass(\Eccube\Entity\ProductClass $productClass = null)
    {
        $this->ProductClass = $productClass;

        return $this;
    }

    /**
     * Get productClass.
     *
     * @return \Eccube\Entity\ProductClass|null
     */
    public function getProductClass()
    {
        return $this->ProductClass;
    }
}
