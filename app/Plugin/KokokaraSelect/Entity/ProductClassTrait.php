<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Trait ProductClassTrait
 * @package Plugin\KokokaraSelect\Entity
 * @Eccube\EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\KokokaraSelect\Entity\KsSelectItem", mappedBy="ProductClass")
     */
    private $KsSelectItems;

    /**
     * @var string
     */
    private $ksOriginStock;

    /**
     * @var bool
     */
    private $ksOriginStockUnlimited = false;

    /**
     * @return Collection
     */
    public function getKsSelectItems()
    {
        return $this->KsSelectItems;
    }

    /**
     * @param Collection $KsSelectItems
     * @return ProductClassTrait
     */
    public function setKsSelectItems(Collection $KsSelectItems)
    {
        $this->KsSelectItems = $KsSelectItems;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getKsOriginStock()
    {
        return $this->ksOriginStock;
    }

    /**
     * @param string|null $ksOriginStock
     * @return ProductClassTrait
     */
    public function setKsOriginStock(?string $ksOriginStock)
    {
        $this->ksOriginStock = $ksOriginStock;
        return $this;
    }

    /**
     * @return bool
     */
    public function isKsOriginStockUnlimited()
    {
        return $this->ksOriginStockUnlimited;
    }

    /**
     * @param bool $ksOriginStockUnlimited
     * @return ProductClassTrait
     */
    public function setKsOriginStockUnlimited(bool $ksOriginStockUnlimited)
    {
        $this->ksOriginStockUnlimited = $ksOriginStockUnlimited;
        return $this;
    }

}
