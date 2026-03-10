<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/26
 */

namespace Plugin\KokokaraSelect\Doctrine\EventListener;


use Eccube\Entity\CartItem;
use Plugin\KokokaraSelect\Entity\KsCartSelectItem;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;

class CartItemEventListener
{

    public function postLoad(CartItem $entity)
    {
        // 読み込みを実施してキャッシュへ焼き付け
        /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
        foreach ($entity->getKsCartSelectItemGroups() as $ksCartSelectItemGroup) {
            /** @var KsCartSelectItem $item */
            foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $item) {
                $item->getKsSelectItem();
            }
        }
    }
}
