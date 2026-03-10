<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/03
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow\Processor;


use Eccube\Annotation\OrderFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseProcessor;
use Plugin\KokokaraSelect\Entity\KsSelectItemStock;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\KsValidatorTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @OrderFlow
 *
 * Class KokokaraSelectStockDiffProcessor
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow\Processor
 */
class KokokaraSelectStockDiffProcessor extends ItemHolderValidator implements PurchaseProcessor
{

    use ConfigHelperTrait;

    use KsValidatorTrait;

    /** @var KsOrderService */
    protected $ksOrderService;

    /** @var Session */
    protected $session;

    public function __construct(
        KsOrderService $ksOrderService,
        SessionInterface $session
    )
    {
        $this->ksOrderService = $ksOrderService;
        $this->session = $session;
    }

    protected function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (is_null($context->getOriginHolder())) {
            return;
        }

        $From = $context->getOriginHolder();
        $To = $itemHolder;
        $diff = $this->getDiffOfQuantities($From, $To);

        $ksSelectItemService = $this->ksOrderService->getKsSelectItemService();

        foreach ($diff as $id => $quantity) {

            $ksSelectItem = $ksSelectItemService->getKsSelectItemById($id);

            if (!$ksSelectItem) {
                // 存在しない場合は在庫チェックなし
                continue;
            }

            if ($ksSelectItemService->isStockUnlimited($ksSelectItem)) {
                continue;
            }

            if ($ksSelectItem->isStockUnlimited()) {
                continue;
            }

            $stock = $ksSelectItem->getStock();
            // 更新後ステータスがキャンセルの場合は, 差分ではなく更新後の個数で確認.
            if ($To->getOrderStatus() && $To->getOrderStatus()->getId() == OrderStatus::CANCEL) {

                $ToItems = $this->ksOrderService->getQuantityByKsSelectItem($To);
                $toQuantity = $ToItems[$id];

                if ($stock - $toQuantity < 0) {
                    $message = trans('kokokara_select.admin.order.select_item.validate.over_stock', [
                        '%product%' => $ksSelectItem->getProductClass()->formattedProductName(),
                        '%kokokara_select%' => $this->getKokokaraSelectName(),
                    ]);
                    $this->throwKsInvalidItemException($message);
                }
            } else {
                if ($stock - $quantity < 0) {
                    $message = trans('kokokara_select.admin.order.select_item.validate.over_stock', [
                        '%product%' => $ksSelectItem->getProductClass()->formattedProductName(),
                        '%kokokara_select%' => $this->getKokokaraSelectName(),
                    ]);
                    $this->throwKsInvalidItemException($message);
                }
            }
        }

    }

    protected function getDiffOfQuantities(ItemHolderInterface $From, ItemHolderInterface $To)
    {
        $FromItems = $this->ksOrderService->getQuantityByKsSelectItem($From);
        $ToItems = $this->ksOrderService->getQuantityByKsSelectItem($To);
        $ids = array_unique(array_merge(array_keys($FromItems), array_keys($ToItems)));

        $diff = [];
        foreach ($ids as $id) {
            // 更新された明細
            if (isset($FromItems[$id]) && isset($ToItems[$id])) {
                // 2 -> 3 = +1
                // 2 -> 1 = -1
                $diff[$id] = $ToItems[$id] - $FromItems[$id];
            } // 削除された明細
            elseif (isset($FromItems[$id]) && empty($ToItems[$id])) {
                // 2 -> 0 = -2
                $diff[$id] = $FromItems[$id] * -1;
            } // 追加された明細
            elseif (!isset($FromItems[$id]) && isset($ToItems[$id])) {
                // 0 -> 2 = +2
                $diff[$id] = $ToItems[$id];
            }
        }

        return $diff;
    }

    public function prepare(ItemHolderInterface $target, PurchaseContext $context)
    {
        if (is_null($context->getOriginHolder())) {
            return;
        }

        $diff = $this->getDiffOfQuantities($context->getOriginHolder(), $target);

        $ksSelectItemService = $this->ksOrderService->getKsSelectItemService();

        foreach ($diff as $id => $quantity) {

            $ksSelectItem = $ksSelectItemService->getKsSelectItemById($id);

            if (!$ksSelectItem) {
                // 存在しない場合は在庫チェックなし
                continue;
            }

            if ($ksSelectItemService->isStockUnlimited($ksSelectItem)) {
                continue;
            }

            if ($ksSelectItem->isStockUnlimited()) {
                continue;
            }

            $stock = $ksSelectItem->getStock() - $quantity;
            $ksSelectItemStock = $ksSelectItem->getKsSelectItemStock();
            if (!$ksSelectItemStock) {
                $ksSelectItemStock = new KsSelectItemStock();
                $ksSelectItemStock->setKsSelectItem($ksSelectItem);
            }
            $ksSelectItem->setStock($stock);
            $ksSelectItemStock->setStock($stock);
        }
    }

    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {
        return;
    }

    public function rollback(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        return;
    }
}
