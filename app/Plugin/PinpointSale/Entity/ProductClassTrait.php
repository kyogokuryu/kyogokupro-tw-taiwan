<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/06/23
 */

namespace Plugin\PinpointSale\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * Trait ProductClassTrait
 * @package Plugin\PinpointSale\Entity
 * @Eccube\EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{

    private $pinpoint_sale_origin_price02;

    private $pinpoint_sale_origin_price02_inc_tax;

    /** @var PinpointSaleItem */
    private $pinpointSaleItem;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\PinpointSale\Entity\ProductPinpoint", mappedBy="ProductClass", cascade={"persist","remove"})
     */
    private $ProductPinpoints;

    /**
     * ProductClassTrait constructor.
     */
    public function __construct()
    {
        $this->ProductPinpoints = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getPinpointSaleOriginPrice02()
    {
        return $this->pinpoint_sale_origin_price02;
    }

    /**
     * @param mixed $pinpoint_sale_origin_price02
     * @return ProductClassTrait
     */
    public function setPinpointSaleOriginPrice02($pinpoint_sale_origin_price02)
    {
        $this->pinpoint_sale_origin_price02 = $pinpoint_sale_origin_price02;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPinpointSaleOriginPrice02IncTax()
    {
        return $this->pinpoint_sale_origin_price02_inc_tax;
    }

    /**
     * @param mixed $pinpoint_sale_origin_price02_inc_tax
     * @return ProductClassTrait
     */
    public function setPinpointSaleOriginPrice02IncTax($pinpoint_sale_origin_price02_inc_tax)
    {
        $this->pinpoint_sale_origin_price02_inc_tax = $pinpoint_sale_origin_price02_inc_tax;
        return $this;
    }

    /**
     * @return PinpointSaleItem
     */
    public function getPinpointSaleItem()
    {
        return $this->pinpointSaleItem;
    }

    /**
     * @param PinpointSaleItem $pinpointSaleItem
     * @return ProductClassTrait
     */
    public function setPinpointSaleItem(?PinpointSaleItem $pinpointSaleItem)
    {
        $this->pinpointSaleItem = $pinpointSaleItem;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getProductPinpoints()
    {
        return $this->ProductPinpoints;
    }

    /**
     * @param Collection $ProductPinpoints
     * @return ProductClassTrait
     */
    public function setProductPinpoints(Collection $ProductPinpoints)
    {
        $this->ProductPinpoints = $ProductPinpoints;
        return $this;
    }

}
