<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/15
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow;

use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Entity\ProductClass;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\KsValidatorTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @ShoppingFlow
 *
 * Class KokokaraSelectOrderItemStockValidator
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow
 */
class KokokaraSelectOrderItemStockValidator extends ItemHolderValidator
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
        // 受注全体での在庫チェック
        if (!$itemHolder instanceof Order) {
            return;
        }

        $productClassQuantities = $this->ksOrderService->getQuantityByProductClass($itemHolder);

        foreach ($productClassQuantities as $productClassQuantity) {

            /** @var ProductClass $productClass */
            $productClass = $productClassQuantity['productClass'];
            $quantity = $productClassQuantity['quantity'];

            // 在庫制限ありの場合チェック
            if (!$productClass->isStockUnlimited()) {

                $stock = $productClass->getStock();
                if ($stock < $quantity) {
                    $this->throwKsInvalidItemException(trans('purchase_flow.over_stock', ['%name%' => $productClass->formattedProductName()]));
                }
            }
        }
    }
}
