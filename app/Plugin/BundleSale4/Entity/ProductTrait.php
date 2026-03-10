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
use Eccube\Annotation as Eccube;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Eccube\EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait {

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\BundleSale4\Entity\BundleItem", mappedBy="Product", cascade={"persist","remove"})
     * @ORM\OrderBy({
     *     "id"="ASC"
     * })
     */
    private $BundleItems;

    /**
     * @return BundleItem[]|Collection
     */
    public function getBundleItems(): Collection
    {
        if (null === $this->BundleItems) {
            $this->BundleItems = new ArrayCollection();
        }

        return $this->BundleItems;
    }

    /**
     * @param BundleItem $BundleItem
     */
    public function addBundleItem(BundleItem $BundleItem)
    {
        if (null === $this->BundleItems) {
            $this->BundleItems = new ArrayCollection();
        }

        $this->BundleItems[] = $BundleItem;
    }

    /**
     * @param BundleItem $BundleItem
     *
     * @return bool
     */
    public function removeBundleItem(BundleItem $BundleItem)
    {
        if (null === $this->BundleItems) {
            $this->BundleItems = new ArrayCollection();
        }

        return $this->BundleItems->removeElement($BundleItem);
    }
}
