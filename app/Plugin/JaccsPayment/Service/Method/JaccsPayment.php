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

namespace Plugin\JaccsPayment\Service\Method;

use Doctrine\ORM\EntityManager;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\CartService;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\MailService as ShoppingService;
use Plugin\JaccsPayment\Entity\PaymentStatus;
use Plugin\JaccsPayment\Entity\ReOrder;
use Plugin\JaccsPayment\Lib\HttpSend;
use Plugin\JaccsPayment\Lib\JaccsException;
use Plugin\JaccsPayment\Repository\ConfigRepository;
use Plugin\JaccsPayment\Repository\PaymentStatusRepository;
use Plugin\JaccsPayment\Util\CreateRequest;
use Plugin\JaccsPayment\Util\MailService;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class JaccsPayment implements PaymentMethodInterface
{
    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var PurchaseFlow
     */
    protected $shoppingPurchaseFlow;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var CreateRequest
     */
    protected $createRequest;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Order
     */
    private $Order;

    /**
     * @var PaymentStatusRepository
     */
    protected $paymentStatusRepository;

    /**
     * @var ShoppingService
     */
    protected $shoppingService;

    /**
     * JaccsPayment constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param ConfigRepository $configRepository
     * @param CartService $cartService
     * @param EntityManager $entityManager
     * @param CreateRequest $createRequest
     * @param MailService $mailService
     * @param Router $router
     * @param Session $session
     * @param PaymentStatusRepository $paymentStatusRepository
     * @param ShoppingService $shoppingService
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        ConfigRepository $configRepository,
        CartService $cartService,
        EntityManager $entityManager,
        CreateRequest $createRequest,
        MailService $mailService,
        Router $router,
        Session $session,
        PaymentStatusRepository $paymentStatusRepository,
        ShoppingService $shoppingService
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->shoppingPurchaseFlow = $shoppingPurchaseFlow;
        $this->configRepository = $configRepository;
        $this->cartService = $cartService;
        $this->entityManager = $entityManager;
        $this->createRequest = $createRequest;
        $this->mailService = $mailService;
        $this->router = $router;
        $this->session = $session;
        $this->paymentStatusRepository = $paymentStatusRepository;
        $this->shoppingService = $shoppingService;
    }

    /**
     * @return PaymentResult
     */
    public function verify()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * @return PaymentResult
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function checkout()
    {
        $config = $this->configRepository->get();

        $result = new PaymentResult();
        $result->setSuccess(false);

        if (!$config) {
            //$result->setErrors([trans('jaccs_payment.shopping.api.conn.config')]);
            $this->session->set('eccube.front.shopping.order.id', $this->Order->getId());

            $this->entityManager->flush();

            $response = new RedirectResponse($this->router->generate('jaccs_error'));
            $result->setResponse($response);

            return $result;
        }

        $senData = $this->createRequest->CreateTransaction($config, $this->Order, $_POST['fraudbuster'] ? $_POST['fraudbuster'] : '', false);

        $history = $this->createRequest->CreateEntityHistory($this->Order, $senData);
        $this->entityManager->persist($history);

        try {
            $http = new HttpSend();
            $reData = $http->sendData($senData);
        } catch (JaccsException $jaccsException) {
            //$result->setErrors([trans('jaccs_payment.shopping.api.conn.error')]);
            // mail
            $this->mailService->sendConnErrMail();

            $this->session->set('eccube.front.shopping.order.id', $this->Order->getId());
            $this->entityManager->flush();

            log_error($jaccsException->getMessage(), ['order_id' => $this->Order->getId(), 'jaccs-logid' => 0]);

            $response = new RedirectResponse($this->router->generate('jaccs_error'));
            $result->setResponse($response);

            return $result;
        } catch (\Exception $exception) {
            //$result->setErrors([trans('jaccs_payment.shopping.api.conn.error')]);
            // mail
            $this->mailService->sendConnErrMail();

            $this->session->set('eccube.front.shopping.order.id', $this->Order->getId());
            $this->entityManager->flush();

            log_error($exception->getMessage(), ['order_id' => $this->Order->getId(), 'jaccs-logid' => 0]);

            $response = new RedirectResponse($this->router->generate('jaccs_error'));
            $result->setResponse($response);

            return $result;
        }

        $history = $this->createRequest->CreateEntityHistory($this->Order, $reData);
        $this->entityManager->persist($history);

        switch ($reData->getResult()) {
            case 'OK':
                if ($reData->getTransactionInfo()->getShopOrderId() != $this->Order->getId()) {
                    throw new Exception("アトディーネ受注ID異常。EC:{$this->Order->getId()}|JACCS:".$reData->getTransactionInfo()->getShopOrderId());
                }

                switch ($reData->getTransactionInfo()->getAutoAuthoriresult()) {
                    case 'OK'://支払い成功

                        $this->shoppingPurchaseFlow->commit($this->Order, new PurchaseContext());

                        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
                        $this->Order->setOrderStatus($OrderStatus);
                        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PRE_END);
                        $this->Order->setJaccsPaymentPaymentStatus($PaymentStatus);

                        //完了画面でイベントを追加しOrderStatus結果ページにリライトりする
                        $result->setSuccess(true);

                        break;

                    case 'NG'://支払い失敗

                        $this->session->set('eccube.front.shopping.order.id', $this->Order->getId());
                        //$result->setErrors([trans('jaccs_payment.shopping.ng')]);

                        $response = new RedirectResponse($this->router->generate('jaccs_ng'));
                        $result->setResponse($response);

                        break;
                    case '審査中'://審査中

                        $this->shoppingPurchaseFlow->commit($this->Order, new PurchaseContext());

                        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
                        $this->Order->setOrderStatus($OrderStatus);
                        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_PENDING);
                        $this->Order->setJaccsPaymentPaymentStatus($PaymentStatus);

                        $this->session->set('eccube.front.shopping.order.id', $this->Order->getId());

                        $response = new RedirectResponse($this->router->generate('jaccs_examination_complete'));
                        $result->setResponse($response);

                        $result->setSuccess(true);

                        $this->shoppingService->sendOrderMail($this->Order);

                        break;
                    default:
                        break;
                }

                break;
            case 'NG':

                $re = new ReOrder();
                $re->setCreateDate(new \DateTime('now'));
                $re->setHistory($history);
                $re->setOrder($this->Order);

                $errors = $reData->getErrors()->getErrors();
                //エラー送信と再編集判断用
                $isNoSendAction = true;
                $errorCheck = ['shopCode', 'linkId', 'linkPassword', 'request', 'linkInfo', 'browserInfo', 'customer', 'ship', 'details', 'shopOrderId', 'shopOrderDate', 'address', 'shipAddress'];
                if (count($errors)) {
                    foreach ($errors as $error) {
                        $errorPoint = $error->getErrorPoint();
                        if (in_array($errorPoint, $errorCheck)) {
                            $isNoSendAction = false;
                            break;
                        }
                    }

                    if ($isNoSendAction) {//受注情報を再編集として処理する（お客さんが）入力した情報に関するエラー
                        $this->shoppingPurchaseFlow->commit($this->Order, new PurchaseContext());

                        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
                        $this->Order->setOrderStatus($OrderStatus);
                        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::JACCS_ORDER_ERROR);
                        $this->Order->setJaccsPaymentPaymentStatus($PaymentStatus);

                        $result->setSuccess(true);
                        $re->setType(3);

                        $this->session->set('eccube.front.shopping.order.id', $this->Order->getId());

                        $response = new RedirectResponse($this->router->generate('jaccs_examination_complete'));
                        $result->setResponse($response);

                        $this->mailService->sendReEditConnMail($this->Order->getId());
                        $this->shoppingService->sendOrderMail($this->Order);
                    } else {//管理者にエラーとして処理する
                        $this->session->set('eccube.front.shopping.order.id', $this->Order->getId());

                        //$result->setErrors([trans('jaccs_payment.shopping.ng')]);

                        $this->mailService->sendReEditMail($this->Order->getId());

                        $response = new RedirectResponse($this->router->generate('jaccs_error'));
                        $result->setResponse($response);
                    }
                } else {//管理者にエラーとして処理する
                    $this->session->set('eccube.front.shopping.order.id', $this->Order->getId());

                    // mail
                    //$result->setErrors([trans('jaccs_payment.shopping.ng')]);
                    $re->setType(4);

                    $this->mailService->sendConnErrMail();

                    $response = new RedirectResponse($this->router->generate('jaccs_error'));
                    $result->setResponse($response);
                }

                $this->Order->addJaccsReOrder($re);
                $this->entityManager->persist($re);

                break;
            default:
                break;
        }

        $this->entityManager->flush();

        return $result;
    }

    /**
     * @return \Eccube\Service\Payment\PaymentDispatcher|void
     *
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function apply()
    {
        $no = $this->Order->getId();

        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        // purchaseFlow::prepareを呼び出し, 購入処理を進める.
        $this->shoppingPurchaseFlow->prepare($this->Order, new PurchaseContext());
    }

    /**
     * @param FormInterface $form
     *
     * @return \Eccube\Service\Payment\PaymentMethod|void
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * @param Order $Order
     *
     * @return \Eccube\Service\Payment\PaymentMethod|void
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
}
