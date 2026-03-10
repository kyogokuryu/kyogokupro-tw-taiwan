<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * Trait ProductTrait
 * @package Plugin\CartInRecommend\Entity
 * @Eccube\EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{

    /**
     * @var KsProduct
     *
     * @ORM\OneToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsProduct", mappedBy="Product")
     */
    private $KsProduct;

    /**
     * @var KsProductOption
     *
     * @ORM\OneToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsProductOption", mappedBy="Product", cascade={"persist", "remove"})
     */
    private $KsProductOption;

    /**
     * @return KsProduct
     */
    public function getKsProduct()
    {
        return $this->KsProduct;
    }

    /**
     * @param KsProduct $KsProduct
     * @return ProductTrait
     */
    public function setKsProduct(?KsProduct $KsProduct)
    {
        $this->KsProduct = $KsProduct;
        return $this;
    }

    /**
     * @return KsProductOption
     */
    public function getKsProductOption()
    {
        return $this->KsProductOption;
    }

    /**
     * @param KsProductOption $KsProductOption
     * @return ProductTrait
     */
    public function setKsProductOption(?KsProductOption $KsProductOption)
    {
        $this->KsProductOption = $KsProductOption;
        return $this;
    }

}
