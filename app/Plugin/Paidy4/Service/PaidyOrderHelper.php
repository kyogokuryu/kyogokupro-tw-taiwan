<?php

namespace Plugin\Paidy4\Service;

use Plugin\Paidy4\Repository\ConfigRepository;
use Plugin\Paidy4\Service\Method\Paidy;
use Plugin\Paidy4\Service\PaidyRequestService;
use Plugin\Paidy4\Util\PluginUtil as PaidyPluginUtil;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\Master\OrderStatusRepository;

class PaidyOrderHelper
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PaidyRequestService
     */
    protected $paidyRequestService;

    public function __construct(
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        OrderStatusRepository $orderStatusRepository,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        PaidyRequestService $paidyRequestService
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->paidyRequestService = $paidyRequestService;

        $this->Config = $this->configRepository->get();
    }

    /**
     * Paidyに送信するjsonデータの作成
     *
     * @param  Order  $Order     受注データ
     *
     * @return array  $arrPaidy  Paidy送信用決済情報
     */
    public function getPaidyOrderData($Order)
    {
        $arrPaidy['amount']       = intval((float) $Order->getPaymentTotal());
        $arrPaidy['currency']     = (string) $this->eccubeConfig['paidy']['currency'];
        $arrPaidy['store_name']   = (string) $this->Config['store_name'];
        $arrPaidy['description']  = (string) " ";
        $arrPaidy['metadata']     = array('Platform' => 'EC-CUBE ver4');

        // buyer object
        $arrPaidy['buyer'] = $this->createBuyerObject($Order);

        // buyer_data object
        $arrPaidy['buyer_data'] = $this->createBuyerDataObject($Order);

        // order object
        $arrPaidy['order'] = $this->createOrderObject($Order);

        // shipping_address object
        $arrPaidy['shipping_address'] = $this->createShippingAddressObject($Order);

        return $arrPaidy;
    }

    /**
     * createBuyerObject
     */
    public function createBuyerObject($Order)
    {
        $buyerObject['email'] = (string) $Order->getEmail();
        $buyerObject['name1'] = (string) $Order->getName01() . ' ' . $Order->getName02();
        $buyerObject['name2'] = (string) $Order->getKana01() . ' ' . $Order->getKana02();
        $buyerObject['phone'] = (string) $Order->getPhoneNumber();
        if ($Order->getBirth()) {
            $buyerObject['dob'] = (string) $Order->getBirth()->format('Y-m-d');
        }

        return $buyerObject;
    }

    /**
     * createBuyerDataObject
     */
    public function createBuyerDataObject($Order)
    {
        $buyerDataObject['age']               = (int) 0;
        $buyerDataObject['order_count']       = (int) 0;
        $buyerDataObject['ltv']               = (int) 0;
        $buyerDataObject['last_order_amount'] = (int) 0;
        $buyerDataObject['last_order_at']     = (int) 0;

        if ($Order->getCustomer()) {
            $Customer = $Order->getCustomer();
            $buyerDataObject['user_id'] = (string) $Customer->getId();
            $buyerDataObject['age']     = (int) PaidyPluginUtil::getDayDiff($Customer->getCreateDate()->format('Y-m-d'));

            $arrOrderHistory = [];
            $life_time_value = 0;
            // order status counted as order history
            $arrTargetOrderStatuses = [
                OrderStatus::NEW,
                OrderStatus::IN_PROGRESS,
                OrderStatus::DELIVERED,
                OrderStatus::PAID
            ];
            foreach ($arrTargetOrderStatuses as $orderStatus) {
                $arrCustomerOrderHistory = $this->orderRepository->findBy([
                    'Customer' => $Customer,
                    'OrderStatus' => $orderStatus,
                ]);
                if (empty($arrCustomerOrderHistory)) {
                    continue;
                }
                foreach ($arrCustomerOrderHistory as $customerOrderHistory) {
                    if ($customerOrderHistory->getPayment()->getMethodClass() != Paidy::class) {
                        // count without Paidy order
                        $order_id = $customerOrderHistory['id'];
                        $arrOrderHistory[$order_id] = $customerOrderHistory;
                        $life_time_value += $customerOrderHistory['payment_total'];
                    }
                }
            }

            if (!empty($arrOrderHistory)) {
                $max_order_id = max(array_keys($arrOrderHistory));
                $lastOrder = $arrOrderHistory[$max_order_id];
                $buyerDataObject['order_count']       = (int) count($arrOrderHistory);
                $buyerDataObject['ltv']               = (int) $life_time_value;
                $buyerDataObject['last_order_amount'] = (int) $lastOrder['payment_total'];
                $buyerDataObject['last_order_at']     = (int) PaidyPluginUtil::getDayDiff($lastOrder['create_date']->format('Y-m-d'));
            }
        }

        if ($Order->isMultiple()) {
            // set additional_shipping_addresses when multiple shipping
            foreach ($Order->getShippings() as $key => $Shipping) {
                if ($key == 0) {
                    // skip first shipping info
                    continue;
                }

                $arrAddr['line1'] = (string) "";
                $arrAddr['line2'] = (string) $Shipping->getAddr02();
                $arrAddr['city']  = (string) $Shipping->getAddr01();
                $arrAddr['state'] = (string) $Shipping->getPref();
                $arrAddr['zip']   = (string) PaidyPluginUtil::convertPostalCode($Shipping->getPostalCode());
                $buyerDataObject['additional_shipping_addresses'][] = $arrAddr;
            }
        }

        return $buyerDataObject;
    }

    /**
     * createOrderObject
     */
    public function createOrderObject($Order)
    {
        $orderObject['items'] = [];

        $OrderItems = $this->orderItemRepository->findBy(['Order' => $Order]);
        $deliv_fee = $tax = 0;
        foreach ($OrderItems as $OrderItem) {
            $arrItem = [];
            if ($OrderItem->isDeliveryFee()) {
                // deliv_fee
                $deliv_fee += (int) intval($OrderItem->getPrice() * $OrderItem->getQuantity());
            } else {
                if ($OrderItem->getPrice() != 0) {
                    if ($OrderItem->isProduct()) {
                        // product
                        $ProductClass = $OrderItem->getProduct();
                        $arrItem['id']     = (string) $ProductClass->getId();
                        $arrItem['title']       = (string) $OrderItem->getProductName();
                        // tax
                        $tax += $OrderItem->getTax() * $OrderItem->getQuantity();
                    } else {
                        // not product
                        if ($OrderItem->isPoint()) {
                            $arrItem['title'] = 'ポイント値引き';
                        } elseif($OrderItem->isCharge()) {
                            $arrItem['title'] = '手数料';
                        } elseif($OrderItem->isDiscount()) {
                            $arrItem['title'] = '値引き';
                        } else {
                            $arrItem['title'] = (string) $OrderItem->getProductName();
                        }
                    }
                    $arrItem['quantity']    = (int) $OrderItem->getQuantity();
                    $arrItem['unit_price']  = (int) intval((float) $OrderItem->getPrice());
                    $arrItem['description'] = (string) " ";
                    $orderObject['items'][] = $arrItem;
                }
            }
        }

        $orderObject['order_ref'] = (string) $Order->getId();
        $orderObject['shipping']  = (int) intval((float) $deliv_fee);
        $orderObject['tax']       = (int) intval((float) $tax);

        return $orderObject;
    }

    /**
     * createShippingAddressObject
     */
    public function createShippingAddressObject($Order)
    {
        $Shipping = $Order->getShippings()->first();

        $shippingAddressObject['line1'] = (string) "";
        $shippingAddressObject['line2'] = (string) $Shipping->getAddr02();
        $shippingAddressObject['city']  = (string) $Shipping->getAddr01();
        $shippingAddressObject['state'] = (string) $Shipping->getPref();
        $shippingAddressObject['zip']   = (string) PaidyPluginUtil::convertPostalCode($Shipping->getPostalCode());

        return $shippingAddressObject;
    }

    /**
     * Paidyの受注情報を登録
     *
     * @param Order  $Order     対象受注
     * @param array  $ApiResult API実行結果
     */
    public function setPaidyOrder($Order, $ApiResult)
    {
        // 受注ステータスを新規受付へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
        $Order->setOrderStatus($OrderStatus);

        // Paidy決済ID設定
        $Order->setPaidyOrderId((string)$ApiResult['id']);

        // Paidy決済金額設定
        $Order->setPaidyPaymentTotal((int)$ApiResult['amount']);

        // Paidyステータス設定
        $Order->setPaidyStatus((string)$ApiResult['status']);

        // Paidy有効期限設定
        $expires_at = date('Y-m-d H:i:s', strtotime($ApiResult['expires_at']));
        $Order->setPaidyExpireDate(new \DateTime($expires_at));
    }

    /**
     * Paidyの受注情報を更新
     *
     * @param Order  $Order       対象受注
     * @param array  $ApiResult   API実行結果
     *
     * @return bool  $update_flg  更新有無
     */
    public function updatePaidyOrder($Order, $ApiResult)
    {
        $update_flg = false;

        // Paidy状態を設定
        if ($Order->getPaidyStatus() != $ApiResult['status'] && ($ApiResult['status'] == 'authorized' || $ApiResult['status'] == 'closed')) {
            $Order->setPaidyStatus($ApiResult['status']);
            $update_flg = true;
        }

        // キャプチャー情報を設定
        if (!empty($ApiResult['captures'])) {
            foreach ($ApiResult['captures'] as $capture) {
                // PaidyキャプチャーIDを設定
                if ($Order->getPaidyCaptureId() != $capture['id']) {
                    $Order->setPaidyCaptureId((string)$capture['id']);
                    $update_flg = true;
                }
                // Paidyキャプチャー額を設定
                if ($Order->getPaidyCaptureTotal() != $capture['amount']) {
                    $Order->setPaidyCaptureTotal((string)$capture['amount']);
                    $update_flg = true;
                }
                // 入金日を設定
                if ($Order->getPaymentDate() == null) {
                    $Order->setPaymentDate(new \DateTime($capture['created_at']));
                    $update_flg = true;
                }
            }
        }

        // リファンド情報を設定
        if (!empty($ApiResult['refunds'])) {
            // Paidyリファンド金額を設定
            $paidy_refund_total = 0;
            foreach ($ApiResult['refunds'] as $refund) {
                $paidy_refund_total += $refund['amount'];
            }
            if ($Order->getPaidyRefundTotal() != $paidy_refund_total) {
                $Order->setPaidyRefundTotal((int)$paidy_refund_total);
                $update_flg = true;
            }
        }

        return $update_flg;
    }
}
