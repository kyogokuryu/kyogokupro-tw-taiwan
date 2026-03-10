<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/25
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow;


use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Order;
use Eccube\Repository\ProductClassRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Entity\KsOrderItemEx;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\KsValidatorTrait;

/**
 * @ShoppingFlow
 *
 * Class KokokaraSelectOrderItemValidator
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow
 */
class KokokaraSelectOrderItemValidator extends ItemHolderValidator
{

    use ConfigHelperTrait;

    use KsValidatorTrait;

    /** @var CartService */
    protected $cartService;

    /** @var KsService */
    protected $ksService;

    /** @var KsOrderService */
    protected $ksOrderService;

    /** @var ProductClassRepository */
    protected $productClassRepository;

    public function __construct(
        CartService $cartService,
        KsService $ksService,
        KsOrderService $ksOrderService,
        ProductClassRepository $productClassRepository
    )
    {
        $this->cartService = $cartService;
        $this->ksService = $ksService;
        $this->ksOrderService = $ksOrderService;
        $this->productClassRepository = $productClassRepository;
    }

    protected function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {

        if (!$itemHolder instanceof Order) {
            return;
        }

        // 選択商品チェック
        $validResults = $this->ksOrderService->validOrderItem($itemHolder);

        foreach ($validResults as $productClassId => $validResult) {

            if (!$validResult['valid']) {
                // エラー有り
                // 構成エラー
                if (isset($validResult['ksSelectItemStructureErrors'])) {
                    foreach ($validResult['ksSelectItemStructureErrors'] as $ksSelectItemStructureError) {
                        if (isset($ksSelectItemStructureError['NG'])) {
                            // 選択数量が適切でない
                            $productClass = $this->productClassRepository->find($productClassId);
                            $this->throwKsInvalidItemException('kokokara_select.cart.item.validate.quantity',
                                ['%kokokara_select%' => $this->getKokokaraSelectName()], $productClass);
                        }
                    }
                }

                // グループエラー
                if (isset($validResult['ksSelectItemGroupErrors'])) {
                    foreach ($validResult['ksSelectItemGroupErrors'] as $ksSelectItemGroupError) {
                        if (isset($ksSelectItemGroupError['NGQuantity'])) {
                            // 選択数量が適切でない
                            $productClass = $this->productClassRepository->find($productClassId);
                            $this->throwKsInvalidItemException('kokokara_select.cart.item.validate.quantity',
                                ['%kokokara_select%' => $this->getKokokaraSelectName()], $productClass);
                        }
                    }
                }
                // 商品エラー
                if (isset($validResult['ksSelectItemErrors'])) {
                    foreach ($validResult['ksSelectItemErrors'] as $ksSelectItemError) {
                        foreach ($ksSelectItemError as $itemError) {
                            if (isset($itemError['NoItem'])) {
                                // 選択不可商品
                                // カート画面に戻った際削除
                                $this->throwInvalidItemException('kokokara_select.cart.item.validate.item');
                            }
                        }
                    }
                }
                // 在庫エラー
                if (isset($validResult['NGStock'])) {
                    $productClass = $this->productClassRepository->find($productClassId);

                    /** @var KsSelectItem $targetKsSelectItem */
                    $targetKsSelectItem = $validResult['TargetKsSelectItem'];
                    $parentProduct = $targetKsSelectItem->getKsSelectItemGroup()->getKsProduct()->getProduct();

                    if ($validResult['NGStock'] == 0) {
                        // 在庫0
                        if ($this->ksService->isDirectSelectKsProduct($targetKsSelectItem->getProductClass())) {
                            // 固定選択商品
                            $this->throwKsInvalidItemException('front.shopping.out_of_stock', [], $productClass);
                        } else {
                            $this->throwKsInvalidItemException('kokokara_select.cart.select.item.validate.stock_zero',
                                [
                                    '%parent%' => $parentProduct->getName(),
                                    '%kokokara_select%' => $this->getKokokaraSelectName()
                                ], $productClass);
                        }
                    } else {
                        // 在庫以上
                        $ksProduct = $targetKsSelectItem->getKsSelectItemGroup()->getKsProduct();
                        if ($ksProduct->isDirectSelect()) {
                            // 固定選択商品
                            $this->throwKsInvalidItemException('front.shopping.out_of_stock', [], $productClass);
                        } else {
                            $this->throwKsInvalidItemException('kokokara_select.cart.select.item.validate.over_stock',
                                [
                                    '%parent%' => $parentProduct->getName(),
                                    '%kokokara_select%' => $this->getKokokaraSelectName(),
                                ], $productClass);
                        }
                    }
                }
            }
        }

        // 商品公開チェック
        foreach ($itemHolder->getOrderItems() as $orderItem) {

            if (!$orderItem->isProduct()) continue;

            if ($this->ksService->isKsProduct($orderItem)) {
                // 選択商品親の場合 構成品の公開状態チェック
                /** @var KsOrderItemEx $ksOrderItemChild */
                foreach ($orderItem->getKsOrderItemChildren() as $ksOrderItemChild) {

                    $product = $ksOrderItemChild->getOrderItem()->getProduct();
                    if ($product->getStatus()->getId() != ProductStatus::DISPLAY_SHOW) {
                        // NG
                        $this->throwInvalidItemException('kokokara_select.cart.item.validate.item');
                    }
                }
            }
        }

    }
}
