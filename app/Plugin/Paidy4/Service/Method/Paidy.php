<?php

namespace Plugin\Paidy4\Service\Method;

use Eccube\Entity\Order;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\Form\FormInterface;

class Paidy implements PaymentMethodInterface
{
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * Paidy constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
    }

    /**
     * {@inheritdoc}
     *
     * @return PaymentDispatcher
     */
    public function apply()
    {
        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        // POSTを行うため中継のページを表示
        $dispatcher = new PaymentDispatcher();
        $dispatcher->setForward(true);
        $dispatcher->setRoute('paidy_confirm');

        return $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function checkout()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function verify()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
}
 ?>
