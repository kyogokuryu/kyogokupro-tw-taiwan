<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/23
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow;


use Eccube\Annotation\CartFlow;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\ProductClass;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Entity\KsCartSelectItem;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsCartHelper;
use Plugin\KokokaraSelect\Service\KsSelectItemService;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\KsValidatorTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @CartFlow
 *
 * Class KokokaraSelectItemStockValidator
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow
 */
class KokokaraSelectItemStockValidator extends ItemHolderValidator
{

    use ConfigHelperTrait;

    use KsValidatorTrait;

    /** @var KsService */
    protected $ksService;

    /** @var KsCartHelper */
    protected $ksCartHelper;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var Session */
    protected $session;

    public function __construct(
        KsService $ksService,
        KsCartHelper $ksCartHelper,
        KsSelectItemService $ksSelectItemService,
        SessionInterface $session
    )
    {
        $this->ksService = $ksService;
        $this->ksCartHelper = $ksCartHelper;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->session = $session;
    }

    /**
     * 在庫数チェック
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     * @throws \Eccube\Service\PurchaseFlow\InvalidItemException
     */
    protected function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {

        $targetList = [];
        $addTargetList = [];
        $quantityList = [];
        $productClasses = [];
        $directSelect = [];

        $activeKsCartKey = $this->ksService->getActiveKsCartKey();
        $isAddCartRoute = $this->ksCartHelper->isSelectItemAddCartRoute();

        if ($itemHolder instanceof Cart) {

            // 商品ごとの必要数量
            /** @var CartItem $cartItem */
            foreach ($itemHolder->getItems() as $cartItem) {

                if ($cartItem->isProduct()) {
                    $productClassId = $cartItem->getProductClass()->getId();

                    if (isset($quantityList[$productClassId])) {
                        $quantityList[$productClassId] += $cartItem->getQuantity();
                    } else {
                        $quantityList[$productClassId] = $cartItem->getQuantity();
                    }

                    $checkTarget = true;
                    if ($isAddCartRoute) {
                        if ($activeKsCartKey
                            && $cartItem->getKsCartSelectItemGroup($activeKsCartKey)) {
                            // カート追加時は、追加グループのみチェック
                        } else {
                            $checkTarget = false;
                        }
                    }

                    // カート明細ごとの在庫チェック
                    if ($checkTarget) {
                        $cartItemQuantities = $this->ksCartHelper->getKsCartSelectItemQuantities($cartItem);
                        foreach ($cartItemQuantities as $cartItemQuantity) {
                            /** @var KsSelectItem $ksSelectItem */
                            $ksSelectItem = $cartItemQuantity['ksSelectItem'];
                            $quantity = $cartItemQuantity['quantity'];
                            $productClass = $ksSelectItem->getProductClass();

                            // 親商品
                            $parentProduct = $cartItem->getProductClass()->getProduct();

                            // 在庫チェック
                            $validStock = $this->ksSelectItemService->validStock($ksSelectItem, $quantity);

                            if (!$validStock['result']) {

                                if (!$productClass->isStockUnlimited() && $productClass->getStock() == 0) {
                                    // 商品の在庫が存在しない場合
                                    $this->throwKsInvalidItemException('kokokara_select.cart.item.validate.stock_zero',
                                        ['%kokokara_select%' => $this->getKokokaraSelectName()], $productClass, true);
                                }

                                if ($validStock['stock'] == 0) {
                                    // 在庫0
                                    if ($this->ksService->isDirectSelectKsProduct($cartItem->getProductClass())) {
                                        // 固定選択商品
                                        $this->throwKsInvalidItemException('front.shopping.out_of_stock', [], $productClass, true);
                                    } else {
                                        $this->throwKsInvalidItemException('kokokara_select.cart.select.item.validate.stock_zero',
                                            [
                                                '%parent%' => $parentProduct->getName(),
                                                '%kokokara_select%' => $this->getKokokaraSelectName(),
                                            ], $productClass, true);
                                    }
                                } else {
                                    // 在庫以上
                                    if ($this->ksService->isDirectSelectKsProduct($cartItem->getProductClass())) {
                                        // 固定選択商品
                                        $this->throwKsInvalidItemException('front.shopping.out_of_stock', [], $productClass, true);
                                    } else {
                                        $this->throwKsInvalidItemException('kokokara_select.cart.select.item.validate.over_stock',
                                            [
                                                '%parent%' => $parentProduct->getName(),
                                                '%kokokara_select%' => $this->getKokokaraSelectName()
                                            ], $productClass, true);
                                    }
                                }
                            }
                        }
                    }

                    /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
                    foreach ($cartItem->getKsCartSelectItemGroups() as $ksCartSelectItemGroup) {

                        /** @var KsCartSelectItem $ksCartSelectItem */
                        foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {

                            $productClass = $ksCartSelectItem->getKsSelectItem()->getProductClass();

                            $productClasses[$productClass->getId()] = $productClass;
                            if ($this->ksService->isDirectSelectKsProduct($cartItem->getProductClass())) {
                                // 固定選択商品
                                $directSelect[$productClass->getId()] = true;
                            }

                            $targetList[$productClass->getId()] = $ksCartSelectItem->getKsSelectItem();
                            if (isset($quantityList[$productClass->getId()])) {
                                $quantityList[$productClass->getId()] += $ksCartSelectItem->getQuantity();
                            } else {
                                $quantityList[$productClass->getId()] = $ksCartSelectItem->getQuantity();
                            }

                            if ($isAddCartRoute) {
                                if ($activeKsCartKey
                                    && $ksCartSelectItemGroup->getKsCartKey() == $activeKsCartKey) {
                                    // カート追加時は、追加グループのみチェック
                                    $addTargetList[$productClass->getId()] = true;
                                }
                            }
                        }
                    }
                }
            }

            if ($isAddCartRoute && empty($addTargetList)) {
                // 選択商品でないカート追加ではチェックしない
                return;
            }

            // トータルでの在庫チェック
            foreach ($targetList as $productClassId => $targetKsCartSelectItem) {

                if (!empty($addTargetList)) {
                    // 追加分チェック
                    if (isset($addTargetList[$productClassId])) {
                        // チェック
                        $targetQuantity = $quantityList[$productClassId];
                    } else {
                        continue;
                    }

                } else {
                    $targetQuantity = $quantityList[$productClassId];
                }

                /** @var ProductClass $productClass */
                $productClass = $productClasses[$productClassId];

                if (!$productClass->isStockUnlimited()) {
                    $stock = $productClass->getStock();
                    if ($stock == 0) {
                        // 在庫0
                        if (isset($directSelect[$productClassId])) {
                            $this->throwKsInvalidItemException('front.shopping.out_of_stock', [], $productClass, true);

                        } else {
                            $this->throwKsInvalidItemException('kokokara_select.cart.item.validate.stock_zero',
                                ['%kokokara_select%' => $this->getKokokaraSelectName()], $productClass, true);
                        }
                    } else if ($stock < $targetQuantity) {
                        // 在庫以上
                        if (isset($directSelect[$productClassId])) {
                            $this->throwKsInvalidItemException('front.shopping.out_of_stock', [], $productClass, true);

                        } else {
                            $this->throwKsInvalidItemException('kokokara_select.cart.item.validate.over_stock',
                                ['%kokokara_select%' => $this->getKokokaraSelectName()], $productClass, true);
                        }
                    }
                }
            }

        }
    }
}
