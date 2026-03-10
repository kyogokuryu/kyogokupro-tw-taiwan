<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/08/19
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Annotation\CartFlow;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Entity\KsCartSelectItem;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Service\KsCartHelper;
use Plugin\KokokaraSelect\Service\KsService;

/**
 * @CartFlow
 *
 * KokokaraSelectItemStockValidatorより前に実行
 *
 * Class KokokaraSelectItemHolderPreprocessor
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow\Processor
 */
class KokokaraSelectItemHolderPreprocessor extends ItemHolderValidator
{

    /** @var KsService */
    protected $ksService;

    /** @var KsCartHelper */
    protected $ksCartHelper;

    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(
        KsService $ksService,
        KsCartHelper $ksCartHelper,
        EntityManagerInterface $entityManager
    )
    {
        $this->ksService = $ksService;
        $this->ksCartHelper = $ksCartHelper;
        $this->entityManager = $entityManager;
    }

    /**
     * 数量に合わせて自動選択商品を最適化
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {

        if (!$itemHolder instanceof Cart) {
            return;
        }

        // cart key
        $cartKey = $itemHolder->getCartKey();

        /** @var CartItem $cartItem */
        foreach ($itemHolder->getItems() as $cartItem) {

            $productClass = $cartItem->getProductClass();
            if (!$this->ksService->isKsProduct($productClass)) {
                // 選択商品でない場合
                continue;
            }

            $ksProduct = $productClass->getProduct()->getKsProduct();
            if (!$ksProduct->isDirectSelect()) {
                // 固定セット商品のみ処理
                continue;
            }

            $groupCount = $cartItem->getKsCartSelectItemGroups()->count();

            if ($cartItem->getQuantity() == $groupCount) {
                // 数量一致
                continue;
            }

            // 数量が一致しない場合調整

            $diff = $cartItem->getQuantity() - $groupCount;

            if ($diff > 0) {
                // 追加
                $this->addKsCartItem($cartKey, $cartItem, $diff);
            } else {
                // 削除
                $this->removeKsCartItem($cartKey, $cartItem, abs($diff));
            }
        }
    }

    /**
     * 自動選択商品追加
     *
     * @param string $cartKey cartKey
     * @param CartItem $cartItem
     * @param integer $addQuantity 追加数量
     */
    private function addKsCartItem($cartKey, CartItem $cartItem, $addQuantity)
    {

        $ksProduct = $cartItem->getProductClass()->getProduct()->getKsProduct();

        // カート persist
        if (is_null($cartItem->getId())) {
            $cart = $cartItem->getCart();
            if (is_null($cart->getId())) {
                $this->entityManager->persist($cartItem->getCart());
            }

            $this->entityManager->persist($cartItem);
        }

        // 追加
        for ($i = 0; $i < $addQuantity; $i++) {

            $random = $this->ksCartHelper->createKsCartKey();

            $ksCartSelectItemGroup = new KsCartSelectItemGroup();
            $ksCartSelectItemGroup
                ->setCartKey($cartKey)
                ->setCartItem($cartItem)
                ->setKsCartKey($random);

            $this->entityManager->persist($ksCartSelectItemGroup);

            /** @var KsSelectItemGroup $ksSelectItemGroup */
            foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {

                /** @var KsSelectItem $ksSelectItem */
                foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {

                    if ($ksSelectItem->getProductClass()->getProduct()->getStatus()->getId() != ProductStatus::DISPLAY_SHOW) {
                        continue;
                    }

                    $ksCartSelectItem = new KsCartSelectItem();
                    $ksCartSelectItem
                        ->setKsCartSelectItemGroup($ksCartSelectItemGroup)
                        ->setKsSelectItem($ksSelectItem)
                        ->setQuantity($ksSelectItem->getQuantity());

                    $this->entityManager->persist($ksCartSelectItem);

                    $ksCartSelectItemGroup
                        ->addKsCartSelectItem($ksCartSelectItem);
                }
            }

            $this->entityManager->persist($ksCartSelectItemGroup);

            $cartItem->addKsCartSelectItemGroup($ksCartSelectItemGroup);
        }
    }

    /**
     * 自動選択商品削除
     *
     * @param string $cartKey cartKey
     * @param CartItem $cartItem
     * @param integer $delQuantity 削除数量
     */
    private function removeKsCartItem($cartKey, CartItem $cartItem, $delQuantity)
    {
        for ($i = 0; $i < $delQuantity; $i++) {

            $max = $cartItem->getKsCartSelectItemGroups()->count();

            if ($max == 0) {
                return;
            }

            $ksCartSelectItemGroup = $cartItem->getKsCartSelectItemGroups()->get($max - 1);
            $this->entityManager->remove($ksCartSelectItemGroup);

            $cartItem->removeKsCartSelectItemGroup($ksCartSelectItemGroup);

        }
    }
}
