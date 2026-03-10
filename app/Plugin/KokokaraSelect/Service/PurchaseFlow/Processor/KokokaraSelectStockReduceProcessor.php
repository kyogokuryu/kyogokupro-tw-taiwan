<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/24
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow\Processor;


use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Order;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductStock;
use Eccube\Exception\ShoppingException;
use Eccube\Service\PurchaseFlow\Processor\AbstractPurchaseProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemStock;
use Plugin\KokokaraSelect\Service\KsOrderItemService;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\KsSelectItemService;
use Plugin\KokokaraSelect\Service\KsService;

/**
 * 選択商品の在庫調整
 * [独自タイミングで追加]
 *
 * Class KokokaraSelectStockReduceProcessor
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow\Processor
 */
class KokokaraSelectStockReduceProcessor extends AbstractPurchaseProcessor
{

    /** @var KsService */
    protected $ksService;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var OrderItemType */
    protected $ksOrderItemType;

    /** @var OrderItemType */
    protected $productOrderItemType;

    /** @var KsOrderService */
    protected $ksOrderService;

    public function __construct(
        KsService $ksService,
        KsSelectItemService $ksSelectItemService,
        EntityManagerInterface $entityManager,
        KsOrderItemService $ksOrderItemService,
        KsOrderService $ksOrderService
    )
    {
        $this->ksService = $ksService;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->entityManager = $entityManager;
        $this->ksOrderService = $ksOrderService;

        // 商品タイプ
        $this->productOrderItemType = $ksOrderItemService->getProductOrderItemType();
    }

    /**
     * 在庫減らす
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     * @throws ShoppingException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\PessimisticLockException
     */
    public function prepare(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // 在庫をへらす
        $this->eachProductOrderItems($itemHolder, function ($currentStock, $itemQuantity) {
            return $currentStock - $itemQuantity;
        });
    }

    /**
     * 在庫戻す
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     * @throws ShoppingException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\PessimisticLockException
     */
    public function rollback(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // 在庫を戻す
        $this->eachProductOrderItems($itemHolder, function ($currentStock, $itemQuantity) {
            return $currentStock + $itemQuantity;
        });
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param callable $callback
     * @throws ShoppingException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\PessimisticLockException
     */
    private function eachProductOrderItems(ItemHolderInterface $itemHolder, callable $callback)
    {
        if (!$itemHolder instanceof Order) {
            return;
        }

        // 構成品の割当在庫調整
        $quantities = $this->ksOrderService->getQuantityByKsSelectItem($itemHolder);

        foreach ($quantities as $ksSelectItemId => $quantity) {

            /** @var KsSelectItem $ksSelectItem */
            $ksSelectItem = $this->ksSelectItemService->getKsSelectItemById($ksSelectItemId);

            if (!$ksSelectItem) {
                // 商品が存在しない場合
                throw new ShoppingException(trans('kokokara_select.shopping.ks_select_item.none', ['%name%' => $ksSelectItem->getProductClass()->getProduct()->getName()]));
            }

            // 割当在庫ありの場合、在庫を調整
            if (!$this->ksSelectItemService->isStockUnlimited($ksSelectItem)) {
                if (!$ksSelectItem->isStockUnlimited()) {

                    /** @var KsSelectItemStock $ksSelectItemStock */
                    $ksSelectItemStock = $ksSelectItem->getKsSelectItemStock();

                    // 在庫に対してロック実行
                    $this->entityManager->lock($ksSelectItemStock, LockMode::PESSIMISTIC_WRITE);
                    $this->entityManager->refresh($ksSelectItemStock);

                    $stock = $callback($ksSelectItem->getStock(), $quantity);
                    if ($stock < 0) {
                        throw new ShoppingException(trans('purchase_flow.over_stock', ['%name%' => $ksSelectItem->getProductClass()->getProduct()->getName()]));
                    }
                    $ksSelectItemStock->setStock($stock);
                    $ksSelectItem->setStock($stock);
                }
            }
        }

        $productClassQuantities = [];

        // 構成品本体の在庫調整用に数量取得
        foreach ($quantities as $ksSelectItemId => $quantity) {
            /** @var KsSelectItem $ksSelectItem */
            $ksSelectItem = $this->ksSelectItemService->getKsSelectItemById($ksSelectItemId);

            if (!$ksSelectItem) {
                // 商品が存在しない場合
                throw new ShoppingException(trans('kokokara_select.shopping.ks_select_item.none', ['%name%' => $ksSelectItem->getProductClass()->getProduct()->getName()]));
            }

            $productClass = $ksSelectItem->getProductClass();
            if (isset($productClassQuantities[$productClass->getId()])) {
                $productClassQuantities[$productClass->getId()]['quantity'] += $quantity;
            } else {
                $productClassQuantities[$productClass->getId()] = [
                    'productClass' => $productClass,
                    'quantity' => $quantity
                ];
            }
        }

        // 構成品本体の在庫調整
        foreach ($productClassQuantities as $productClassQuantity) {

            /** @var ProductClass $productClass */
            $productClass = $productClassQuantity['productClass'];
            $quantity = $productClassQuantity['quantity'];

            // 在庫制限ありの場合チェック
            if (!$productClass->isStockUnlimited()) {

                /** @var ProductStock $productStock */
                $productStock = $productClass->getProductStock();

                // ロック済みのためロックなし
                $stock = $callback($productStock->getStock(), $quantity);
                if ($stock < 0) {
                    throw new ShoppingException(trans('purchase_flow.over_stock', ['%name%' => $productClass->formattedProductName()]));
                }

                $productStock->setStock($stock);
                $productClass->setStock($stock);
            }
        }

    }
}
