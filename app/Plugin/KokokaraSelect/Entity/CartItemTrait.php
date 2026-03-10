<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * Trait CartItemTrait
 * @package Plugin\KokokaraSelect\Entity
 * @Eccube\EntityExtension("Eccube\Entity\CartItem")
 */
trait CartItemTrait
{

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup", mappedBy="CartItem", cascade={"persist"})
     */
    private $KsCartSelectItemGroups;

    /**
     * @param KsCartSelectItemGroup $cartSelectItemGroup
     * @return $this
     */
    public function addKsCartSelectItemGroup(KsCartSelectItemGroup $cartSelectItemGroup)
    {
        if (!$this->KsCartSelectItemGroups) {
            $this->KsCartSelectItemGroups = new ArrayCollection();
        }

        $this->KsCartSelectItemGroups->add($cartSelectItemGroup);
        return $this;
    }

    /**
     * @param KsCartSelectItemGroup $cartSelectItemGroup
     * @return bool
     */
    public function removeKsCartSelectItemGroup(KsCartSelectItemGroup $cartSelectItemGroup)
    {
        return $this->KsCartSelectItemGroups->removeElement($cartSelectItemGroup);
    }

    /**
     * Clear
     *
     * @return $this
     */
    public function clearKsCartSelectItemGroup()
    {
        if (!$this->KsCartSelectItemGroups) {
            return $this;
        }
        $this->KsCartSelectItemGroups->clear();
        return $this;
    }

    /**
     * @return Collection
     */
    public function getKsCartSelectItemGroups()
    {
        if (!$this->KsCartSelectItemGroups) {
            $this->KsCartSelectItemGroups = new ArrayCollection();
        }
        return $this->KsCartSelectItemGroups;
    }

    /**
     * 選択商品のグループ情報返却
     *
     * @param $ksCartKey
     * @return KsCartSelectItemGroup|null
     */
    public function getKsCartSelectItemGroup($ksCartKey)
    {
        if (empty($ksCartKey)) {
            return null;
        }

        /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
        foreach ($this->KsCartSelectItemGroups as $ksCartSelectItemGroup) {

            if ($ksCartSelectItemGroup->getKsCartKey() == $ksCartKey) {
                return $ksCartSelectItemGroup;
            }
        }

        return null;
    }

    /**
     * 選択商品グループ情報存在チェック
     *
     * @param $ksCartKey
     * @return bool
     */
    public function isKsCartSelectItemGroup($ksCartKey)
    {
        if ($this->getKsCartSelectItemGroup($ksCartKey)) {
            return true;
        }

        return false;
    }
}
