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
 * @Eccube\EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait {

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\BundleSale4\Entity\OrderBundleItem", mappedBy="OrderItem", cascade={"persist","remove"})
     * @ORM\OrderBy({
     *     "id"="ASC"
     * })
     */
    private $OrderBundleItems;

    /**
     * @return OrderBundleItems[]|Collection
     */
    public function getOrderBundleItems(): Collection
    {
        if (null === $this->OrderBundleItems) {
            $this->OrderBundleItems = new ArrayCollection();
        }

        return $this->OrderBundleItems;
    }

    /**
     * @param OrderBundleItem $OrderBundleItem
     */
    public function addOrderBundleItem(OrderBundleItem $OrderBundleItem)
    {
        if (null === $this->OrderBundleItems) {
            $this->OrderBundleItems = new ArrayCollection();
        }

        $this->OrderBundleItems[] = $OrderBundleItem;
    }

    /**
     * @param OrderBundleItem $OrderBundleItem
     *
     * @return bool
     */
    public function removeOrderBundleItem(OrderBundleItem $OrderBundleItem)
    {
        if (null === $this->OrderBundleItems) {
            $this->OrderBundleItems = new ArrayCollection();
        }

        return $this->OrderBundleItems->removeElement($OrderBundleItem);
    }
}
