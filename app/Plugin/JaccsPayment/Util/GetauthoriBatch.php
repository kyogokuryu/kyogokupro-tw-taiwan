<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JaccsPayment\Util;

use Doctrine\ORM\EntityManager;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Plugin\JaccsPayment\Entity\Config;
use Plugin\JaccsPayment\Entity\PaymentStatus;
use Plugin\JaccsPayment\Entity\ShippingRequest;
use Plugin\JaccsPayment\Lib\HttpSend;
use Plugin\JaccsPayment\Lib\JaccsException;
use Plugin\JaccsPayment\Repository\HistoryRepository;
use Plugin\JaccsPayment\Repository\PaymentStatusRepository;
use Plugin\JaccsPayment\Repository\ReOrderRepository;
use Plugin\JaccsPayment\Service\Method\JaccsPayment;

/**
 * Class GetauthoriBatch
 */
class GetauthoriBatch
{
    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var HistoryRepository
     */
    protected $historyRepository;

    /**
     * @var ReOrderRepository
     */
    protected $reOrderRepository;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var CreateRequest
     */
    protected $createRequest;

    /**
     * @var PaymentStatusRepository
     */
    protected $paymentStatusRepository;

    /**
     * GetauthoriBatch constructor.
     *
     * @param PaymentRepository $paymentRepository
     * @param OrderRepository $orderRepository
     * @param HistoryRepository $historyRepository
     * @param ReOrderRepository $reOrderRepository
     * @param EntityManager $entityManager
     * @param CreateRequest $createRequest
     * @param PaymentStatusRepository $paymentStatusRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        OrderRepository $orderRepository,
        HistoryRepository $historyRepository,
        ReOrderRepository $reOrderRepository,
        EntityManager $entityManager,
        CreateRequest $createRequest,
        PaymentStatusRepository $paymentStatusRepository
    ) {
        $this->setTimeOut();
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
        $this->historyRepository = $historyRepository;
        $this->entityManager = $entityManager;
        $this->createRequest = $createRequest;
        $this->reOrderRepository = $reOrderRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
    }

    public function setTimeOut()
    {
        $timeout = ini_get('max_execution_time');
        if (!$timeout) {
            $timeout = 30;
        }

        ini_set('max_execution_time', $timeout);
    }

    /**
     * @param $n
     *
     * @return array|mixed
     */
    public function getBatchOrder($n)
    {
        $payments = $this->paymentRepository->findBy(['method_class' => JaccsPayment::class]);
        if (!count($payments)) {
            return [];
        }

        $paymentStatus = $this->paymentStatusRepository->findBy(['id' => [PaymentStatus::JACCS_ORDER_PENDING, PaymentStatus::JACCS_ORDER_PENDING_MANUAL]]);
        if (!count($paymentStatus)) {
            return [];
        }

        $q = $this->orderRepository->createQueryBuilder('o')
            ->select('o')
            ->where('o.JaccsPaymentPaymentStatus IN (:status)')
            ->setParameter('status', $paymentStatus)
            ->andWhere('o.Payment IN (:payment)')
            ->setParameter('payment', $payments)
            ->orderBy('o.id');

        if ($n) {
            $q->setMaxResults($n);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * @param Config $config
     * @param Order $order
     * @param $transactionId
     * @param bool $isPageBatch
     *
     * @return int
     *
     * @throws JaccsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function Getauthor(Config $config, Order $order, $transactionId, $isPageBatch = false)
    {
        $getauthori = $this->createRequest->CreateGetauthori($config, $transactionId);

        $history = $this->createRequest->CreateEntityHistory($order, $getauthori, $transactionId);
        $this->entityManager->persist($history);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $http = new HttpSend();

        try {
            $objGetauthoriResponse = $http->sendData($getauthori);
        } catch (\Exception $e) {
            log_error($e->getMessage(), ['order_id' => $order->getId(), 'jaccs-logid' => 0]);

            if ($isPageBatch) {
                throw new JaccsException($e->getMessage(), 999999999);
            }
        }

        if ($objGetauthoriResponse) {

            $isCanl = false;

            $history = $this->createRequest->CreateEntityHistory($order, $objGetauthoriResponse, $transactionId);
            $this->entityManager->persist($history);
            $this->entityManager->persist($order);

            $this->reOrderRepository->delReEditOrder($order);

            if ($objGetauthoriResponse->getResult() == 'OK') {
                if ($objGetauthoriResponse->getTransactionInfo()->getTransactionId() != $objGetauthoriResponse->getTransactionInfo()->getTransactionId()) {
                    throw new JaccsException("アトディーネお問い合わせ番号異常。EC:{$order->getId()}|JACCS:".$objGetauthoriResponse->getTransactionInfo()->getTransactionId(), E_ERROR);
                }

                switch ($objGetauthoriResponse->getTransactionInfo()->getAutoAuthoriresult()) {
                    case 'NG':
                        $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_NG));
                        break;
                    case 'OK':
                        $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PRE_END));
                        break;
                    case '審査中':
                        switch ($objGetauthoriResponse->getTransactionInfo()->getManualAuthoriresult()) {
                            case 'OK':
                                $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PRE_END));
                                break;
                            case 'NG':
                                $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_NG));
                                break;
                            case '保留':
                                $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PENDING_MANUAL));
                                break;
                            default:
                                $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PENDING));
                                break;
                        }
                        break;
                    default:
                        break;
                }
            } else {

                foreach ($objGetauthoriResponse->getErrors()->getErrors() as $error) {
                    if ($error->getErrorCode() == 'er000033') {
                        $isCanl = true;
                        break;
                    }
                }

                if ($isCanl) {
                    $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_CANCEL));
                } else {
                    $this->reOrderRepository->addReEditOrder($order, $history, 3);
                }

                //$order->setOrderStatus($this->orderStatusRepository->find(Inc::JACCS_ORDER_ERROR));
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            if ($isCanl) {
                return 4;
            }

            if ($objGetauthoriResponse->getResult() == 'OK') {//通常処理場合
                if ($objGetauthoriResponse->getTransactionInfo()->getManualAuthoriresult() == '保留') {//目視審査場合
                    return 3;
                }

                return 1;
            } elseif ($objGetauthoriResponse->getResult() == 'NG') {//エラー場合
                return 2;
            }
        } else {
            if ($isPageBatch) {
                throw new JaccsException('アトディーネセンターから返信がありません。', 999999999);
            }
        }
    }

    /**
     * @param Config $config
     * @param Order $order
     *
     * @return int
     *
     * @throws JaccsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function Transaction(Config $config, Order $order)
    {
        $transaction = $this->createRequest->CreateTransaction($config, $order, '', true);

        $history = $this->createRequest->CreateEntityHistory($order, $transaction);
        $this->entityManager->persist($history);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $http = new HttpSend();

        try {
            $objTransactionResponse = $http->sendData($transaction);
        } catch (\Exception $e) {
            log_error($e, ['order_id' => $order->getId(), 'jaccs-logid' => 0]);
            throw new JaccsException($e->getMessage(), 999999999);
        }

        if ($objTransactionResponse) {
            $history = $this->createRequest->CreateEntityHistory($order, $objTransactionResponse);
            $this->entityManager->persist($history);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            switch ($objTransactionResponse->getResult()) {
                case 'OK':
                    $this->reOrderRepository->delReEditOrder($order);
                    if ($objTransactionResponse->getTransactionInfo()->getShopOrderId() != $order->getId()) {
                        throw new JaccsException("アトディーネ受注ID異常。EC:{$order->getId()}|JACCS:".$objTransactionResponse->getTransactionInfo()->getShopOrderId());
                    }

                    switch ($objTransactionResponse->getTransactionInfo()->getAutoAuthoriresult()) {
                        case 'OK':
                            $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PRE_END));
                            break;
                        case 'NG':
                            $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_NG));
                            break;
                        case '審査中':
                            $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PENDING));
                            break;
                        default:
                            break;
                    }
                    break;
                case 'NG':
                    $this->reOrderRepository->addReEditOrder($order, $history, 3);
                    //$order->setOrderStatus($this->orderStatusRepository->find(Inc::JACCS_ORDER_ERROR));

                    break;
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            if ($objTransactionResponse->getResult() == 'OK') {//通常処理場合
                if ($objTransactionResponse->getTransactionInfo()->getAutoAuthoriresult() == '審査中') {//目視審査場合
                    return 3;
                }

                return 1;
            } elseif ($objTransactionResponse->getResult() == 'NG') {//エラー場合
                return 2;
            }
        } else {
            throw new JaccsException('アトディーネセンターから返信がありません。', 999999999);
        }
    }

    /**
     * @param Config $config
     * @param Order $order
     * @param $transactionId
     *
     * @return int
     *
     * @throws JaccsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function Modifytransaction(Config $config, Order $order, $transactionId)
    {
        $modifytransaction = $this->createRequest->CreateModifytransaction($config, $order, $transactionId);

        $history = $this->createRequest->CreateEntityHistory($order, $modifytransaction);
        $this->entityManager->persist($history);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $http = new HttpSend();

        try {
            $objModifytransactionResponse = $http->sendData($modifytransaction);
        } catch (\Exception $e) {
            log_error($e->getMessage(), ['order_id' => $order->getId(), 'jaccs-logid' => 0]);
            throw new JaccsException($e->getMessage(), 999999999);
        }

        if ($objModifytransactionResponse) {
            $history = $this->createRequest->CreateEntityHistory($order, $objModifytransactionResponse);
            $this->entityManager->persist($history);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            switch ($objModifytransactionResponse->getResult()) {
                case 'OK':
                    $this->reOrderRepository->delReEditOrder($order);

                    switch ($objModifytransactionResponse->getTransactionInfo()->getAutoAuthoriresult()) {
                        case 'OK':
                            $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PRE_END));
                            break;
                        case 'NG':
                            $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_NG));
                            break;
                        case '審査中':
                            $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PENDING));
                            break;
                        default:
                            break;
                    }
                    break;
                case 'NG':
                    $isCanl = false;

                    foreach ($objModifytransactionResponse->getErrors()->getErrors() as $error) {
                        if ($error->getErrorCode() == 'er000033') {
                            $isCanl = true;
                            break;
                        }
                    }

                    if ($isCanl) {
                        $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_CANCEL));
                    } else {
                        $this->reOrderRepository->addReEditOrder($order, $history, 3);
                    }

                    break;
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            if ($objModifytransactionResponse->getResult() == 'OK') {//通常処理場合
                if ($objModifytransactionResponse->getTransactionInfo()->getAutoAuthoriresult() == '保留') {//目視審査場合
                    return 3;
                }

                return 1;
            } elseif ($objModifytransactionResponse->getResult() == 'NG') {//エラー場合
                return 2;
            }
        } else {
            throw new JaccsException('アトディーネセンターから返信がありません。', 999999999);
        }
    }

    /**
     * @param Config $config
     * @param Order $order
     * @param $transactionId
     *
     * @return int
     *
     * @throws JaccsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function Cancel(Config $config, Order $order, $transactionId)
    {
        $cancel = $this->createRequest->CreateCancel($config, $transactionId);

        $history = $this->createRequest->CreateEntityHistory($order, $cancel);
        $this->entityManager->persist($history);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $http = new HttpSend();

        try {
            /** @var $objCancelResponse \Plugin\JaccsPayment\Lib\Xml\Modifytransaction\Response\TransactionInfo */
            $objCancelResponse = $http->sendData($cancel);
        } catch (\Exception $e) {
            log_error($e->getMessage(), ['order_id' => $order->getId(), 'jaccs-logid' => 0]);
            throw new JaccsException($e->getMessage(), 999999999);
        }

        $isCanl = false;

        if ($objCancelResponse) {
            $history = $this->createRequest->CreateEntityHistory($order, $objCancelResponse);
            $this->entityManager->persist($history);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            switch ($objCancelResponse->getResult()) {
                case 'OK':
                    $this->reOrderRepository->delReEditOrder($order);
                    $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_CANCEL));

                    $reqs = $order->getJaccsShippingRequests();
                    if (count($reqs)) {
                        foreach ($reqs as $req) {
                            $this->entityManager->remove($req);
                        }
                    }

                    break;
                case 'NG':

                    foreach ($objCancelResponse->getErrors()->getErrors() as $error) {
                        if ($error->getErrorCode() == 'er000033') {
                            $isCanl = true;
                            break;
                        }
                    }

                    if ($isCanl) {
                        $order->setJaccsPaymentPaymentStatus($this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_CANCEL));
                    } else {
                        $this->reOrderRepository->addReEditOrder($order, $history, 3);
                    }
                    break;
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            if ($objCancelResponse->getResult() == 'OK' || $isCanl) {//通常処理場合
                return 1;
            } elseif ($objCancelResponse->getResult() == 'NG') {//エラー場合
                return 2;
            }
        } else {
            throw new JaccsException('アトディーネセンターから返信がありません。', 999999999);
        }
    }

    /**
     * @param Config $config
     * @param Order $order
     * @param $transactionId
     * @param $deliverySlipNo
     * @param $deliveryCompanyCode
     * @param $invoiceDate
     * @return array|int
     * @throws JaccsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function ShippingRequest(Config $config, Order $order, $transactionId,
                                    $deliverySlipNo, $deliveryCompanyCode, $invoiceDate)
    {

        $isEx = count($order->getJaccsShippingRequests()) > 0;

        if ($isEx) {
            $shippingRequest = $this->createRequest->CreateUpdateShippingRequest($config, $transactionId,
                $deliverySlipNo, $deliveryCompanyCode, $invoiceDate);
        } else {
            $shippingRequest = $this->createRequest->CreateShippingRequest($config, $transactionId,
                $deliverySlipNo, $deliveryCompanyCode, $invoiceDate);
        }

        $history = $this->createRequest->CreateEntityHistory($order, $shippingRequest);
        $this->entityManager->persist($history);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $http = new HttpSend();

        try {
            /** @var $objShippingRequestResponse \Plugin\JaccsPayment\Lib\Xml\Shippingrequest\Response */
            $objShippingRequestResponse = $http->sendData($shippingRequest);
        } catch (\Exception $e) {
            log_error($e->getMessage(), ['order_id' => $order->getId(), 'jaccs-logid' => 0]);
            throw new JaccsException($e->getMessage(), 999999999);
        }

        if ($objShippingRequestResponse) {
            $history = $this->createRequest->CreateEntityHistory($order, $objShippingRequestResponse);
            $this->entityManager->persist($history);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            switch ($objShippingRequestResponse->getResult()) {
                case 'OK':

                    $enShippingRequest = null;

                    if ($isEx) {
                        $enShippingRequest = $order->getJaccsShippingRequests()[0];
                    } else {
                        $enShippingRequest = new ShippingRequest();
                        $enShippingRequest->setOrder($order);
                        $order->addJaccsShippingRequest($enShippingRequest);
                    }

                    if ($shippingRequest->getTransactionInfo()->getInvoiceDate() != '') {
                        $enShippingRequest->setInvoiceDate(new \DateTime($shippingRequest->getTransactionInfo()->getInvoiceDate()));
                    } else {
                        $enShippingRequest->setInvoiceDate(null);
                    }

                    $enShippingRequest->setDeliverySlipNo($shippingRequest->getTransactionInfo()->getDeliverySlipNo());
                    $enShippingRequest->setDeliveryCompanyCode($shippingRequest->getTransactionInfo()->getDeliveryCompanyCode());
                    $enShippingRequest->setTransactionId($shippingRequest->getTransactionInfo()->getTransactionId());
                    $enShippingRequest->setIsSendRequest(ShippingRequest::SEND_REQUEST);

                    $this->entityManager->persist($enShippingRequest);
                    $this->entityManager->persist($order);
                    $this->entityManager->flush();

                    return 1;
                default:
                    $errMess = [];

                    foreach ($objShippingRequestResponse->getErrors()->getErrors() as $error) {
                        $errMess[$error->getErrorPoint()] = $error->getErrorMessage();
                    }

                    //$errMess['body'] = $shippingRequest->toXmlText();

                    return $errMess;
            }

        } else {
            throw new JaccsException('アトディーネセンターから返信がありません。', 999999999);
        }
    }

    /**
     * @param Config $config
     * @param Order $order
     * @param $transactionId
     * @return array|int
     * @throws JaccsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function ShippingRequestCancel(Config $config, Order $order, $transactionId)
    {

        $isEx = count($order->getJaccsShippingRequests()) > 0;

        if (!$isEx) {
            return 1;
        }

        $shippingRequest = $this->createRequest->CreateShippingRequestCancel($config, $transactionId);
        $history = $this->createRequest->CreateEntityHistory($order, $shippingRequest);
        $this->entityManager->persist($history);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $http = new HttpSend();

        try {
            /** @var $objShippingRequestResponse \Plugin\JaccsPayment\Lib\Xml\Shippingrequest\Response */
            $objShippingRequestResponse = $http->sendData($shippingRequest);
        } catch (\Exception $e) {
            log_error($e->getMessage(), ['order_id' => $order->getId(), 'jaccs-logid' => 0]);
            throw new JaccsException($e->getMessage(), 999999999);
        }

        if ($objShippingRequestResponse) {
            $history = $this->createRequest->CreateEntityHistory($order, $objShippingRequestResponse);
            $this->entityManager->persist($history);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            switch ($objShippingRequestResponse->getResult()) {
                case 'OK':

                    $enShippingRequest = $order->getJaccsShippingRequests()[0];
                    $order->removeJaccsShippingRequest($enShippingRequest);

                    $this->entityManager->persist($order);
                    $this->entityManager->remove($enShippingRequest);

                    $this->entityManager->flush();

                    return 1;
                default:
                    $errMess = [];

                    foreach ($objShippingRequestResponse->getErrors()->getErrors() as $error) {
                        $errMess[$error->getErrorPoint()] = $error->getErrorMessage();
                    }

                    return $errMess;
            }

        } else {
            throw new JaccsException('アトディーネセンターから返信がありません。', 999999999);
        }
    }
}
