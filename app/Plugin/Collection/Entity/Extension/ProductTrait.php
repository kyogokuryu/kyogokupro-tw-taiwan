<?php

/*
 * This file is part of the Collection Plugin
 *
 * Copyright (C) 2019 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Collection\Entity\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Plugin\Collection\Entity\CollectionProduct;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var CollectionProduct[]|CollectionProduct
     *
     * @ORM\OneToMany(targetEntity="Plugin\Collection\Entity\CollectionProduct", mappedBy="Product", cascade={"persist", "remove"})
     * @ORM\OrderBy({
     *     "id"="ASC"
     * })
     */
    private $CollectionProducts;

    /**
     * @return CollectionProduct[]|CollectionProduct
     */
    public function getCollectionProducts()
    {
        if (null === $this->CollectionProducts) {
            $this->CollectionProducts = new ArrayCollection();
        }

        return $this->CollectionProducts;
    }

    /**
     * @param CollectionProduct $collectionProduct
     */
    public function addCollection(CollectionProduct $collectionProduct)
    {
        if (null === $this->CollectionProducts) {
            $this->CollectionProducts = new ArrayCollection();
        }

        $this->CollectionProducts[] = $collectionProduct;
    }

    /**
     * @param Collection $collection
     *
     * @return bool
     */
    public function removeCollection(CollectionProduct $collectionProduct)
    {
        if (null === $this->CollectionProduct) {
            $this->CollectionProducts = new ArrayCollection();
        }

        return $this->CollectionProducts->removeElement($collectionProduct);
    }
}
