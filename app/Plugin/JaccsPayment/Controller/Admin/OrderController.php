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

namespace Plugin\JaccsPayment\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Plugin\JaccsPayment\Entity\Config;
use Plugin\JaccsPayment\Form\Type\Admin\ShippingRequestType;
use Plugin\JaccsPayment\Repository\ConfigRepository;
use Plugin\JaccsPayment\Repository\HistoryRepository;
use Plugin\JaccsPayment\Util\GetauthoriBatch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var GetauthoriBatch
     */
    protected $getauthoriBatch;

    /**
     * @var HistoryRepository
     */
    protected $historyRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * OrderController constructor.
     *
     * @param ConfigRepository $configRepository
     * @param GetauthoriBatch $getauthoriBatch
     * @param HistoryRepository $historyRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        GetauthoriBatch $getauthoriBatch,
        HistoryRepository $historyRepository,
        OrderRepository $orderRepository,
        ValidatorInterface $validator
    ) {
        $this->configRepository = $configRepository;
        $this->getauthoriBatch = $getauthoriBatch;
        $this->historyRepository = $historyRepository;
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->validator = $validator;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function shippingRequestValidation(array $data)
    {
        // 入力チェック
        $errors = [];

        $errors['配送伝票番号'] = $this->validator->validate(
            $data['delivery_slip_no'],
            [
                new Assert\NotBlank(),
                new Assert\Length([
                    'max' => 20,
                    'min' => 5,
                ]),
                new Assert\Regex(
                    ['pattern' => '/^[0-9\-]+$/', 'message' => '半角数字、[-]を入力ください']
                ),
            ]
        );

        $errors['運送会社コード'] = $this->validator->validate(
            $data['delivery_company_code'],
            [
                new Assert\NotBlank(),
                new Assert\Length([
                    'max' => 2,
                    'min' => 2,
                ]),
            ]
        );

        $errors['請求書発行日'] = $this->validator->validate(
            $data['invoice_date'],
            [
                new Assert\Length([
                    'max' => 10,
                    'min' => 10,
                ]),
                new Assert\Regex(
                    ['pattern' => '/^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/', 'message' => 'YYYY/MM/DDを入力ください']
                ),
            ]
        );

        return $errors;
    }

    /**
     * @Route("/%eccube_admin_route%/jaccs_payment/shipping_request", name="jaccs_payment_shipping_request")
     */
    public function shippingRequest(Request $request)
    {

        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {

            if ('POST' === $request->getMethod()) {

                $orderId = $request->get('order_id');
                $deliverySlipNo = $request->get('delivery_slip_no', '');
                $deliveryCompanyCode = $request->get('delivery_company_code', '');
                $invoiceDate = $request->get('invoice_date', '');

                $errors = $this->shippingRequestValidation([
                    'delivery_slip_no' => $deliverySlipNo,
                    'delivery_company_code' => $deliveryCompanyCode,
                    'invoice_date' => $invoiceDate,
                ]);

                $reErr = [];

                foreach ($errors as $key => $error) {
                    /** @var $error \Symfony\Component\Validator\ConstraintViolationList */
                    if ($error->count() != 0) {
                        foreach ($error as $e) {
                            if (!array_key_exists($key, $reErr)) {
                                $reErr[$key] = '';
                            }
                            $reErr[$key] .= $e->getMessage();
                        }
                    }
                }

                if (count($reErr)) {
                    return new JsonResponse(['status' => 'form_error', 'detail' => $reErr]);
                }

                if ($invoiceDate) {
                    if (!strtotime($invoiceDate)) {
                        return new JsonResponse(['status' => 'form_error', 'detail' => ['請求書発行日' => '正しい請求書発行日を入力ください']]);
                    }
                }

                if ($orderId) {

                    $Order = $this->orderRepository->find($orderId);

                    if ($Order) {

                        $reHistoryData = $this->historyRepository->getReHistory($Order);

                        if ($reHistoryData && $reHistoryData->getTransactionId()) {

                            $isOk = $this->getauthoriBatch->ShippingRequest($this->configRepository->get(), $Order, $reHistoryData->getTransactionId(),
                                $deliverySlipNo, $deliveryCompanyCode, $invoiceDate);

                            $reData = [];

                            if ($isOk === 1) {
                                $reData['status'] = "ok";

                                $this->addSuccess('jaccs_payment.admin.order.shipping_shippingrequest_ok', 'admin');

                            } else {
                                $reData['status'] = "error";
                                $reData['detail'] = $isOk;
                            }

                            $reData['data'] = [
                                'order_id' => $orderId,
                                'delivery_slip_no' => $deliverySlipNo,
                                'delivery_company_code' => $deliveryCompanyCode,
                                'invoice_date' => $invoiceDate,
                            ];

                            return new JsonResponse($reData);
                        }
                    }
                }
            }
        }

        throw new BadRequestHttpException();
    }

    /**
     * @Route("/%eccube_admin_route%/jaccs_payment/shipping_request_cancel", name="jaccs_payment_shipping_request_cancel")
     */
    public function shippingRequestCancel(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {

            if ('POST' === $request->getMethod()) {

                $orderId = $request->get('order_id');

                if ($orderId) {

                    $Order = $this->orderRepository->find($orderId);

                    if ($Order) {

                        $reHistoryData = $this->historyRepository->getReHistory($Order);
                        if ($reHistoryData && $reHistoryData->getTransactionId()) {

                            $isOk = $this->getauthoriBatch->ShippingRequestCancel($this->configRepository->get(), $Order, $reHistoryData->getTransactionId());

                            $reData = [];

                            if ($isOk === 1) {
                                $reData['status'] = "ok";

                                $this->addSuccess('jaccs_payment.admin.order.shipping_shippingrequest_cancel_ok', 'admin');

                            } else {
                                $reData['status'] = "error";
                                $reData['detail'] = $isOk;
                            }

                            return new JsonResponse($reData);
                        }


                    }
                }
            }
        }

        throw new BadRequestHttpException();
    }

    /**
     * @Route("/%eccube_admin_route%/jaccs_payment/batch", name="jaccs_payment_admin_batch")
     */
    public function batch(Request $request)
    {
        if ($request->get('mode') == 'start') {
            $this->session->remove('jaccs_payment.admin.batch.error');
            $this->session->remove('jaccs_payment.admin.batch.hori');
            $this->session->remove('jaccs_payment.admin.batch.order_id');
        }

        /** @var $config Config */
        $config = $this->configRepository->get();
        if ($config && $config->getBatchType() === 0) {
            if ($request->get('mode') == 'start') {
                $allOrder = $this->getauthoriBatch->getBatchOrder(0);

                if (count($allOrder)) {
                    $ids = [];
                    foreach ($allOrder as $order) {
                        $ids[] = $order->getId();
                    }

                    $this->session->set('jaccs_payment.admin.batch.order_id', $ids);
                }
            }

            $ids = $this->session->get('jaccs_payment.admin.batch.order_id', []);

            if (count($ids)) {
                $editOrderId = array_slice($ids, 0, 5);
                $this->session->set('jaccs_payment.admin.batch.order_id', array_slice($ids, 5));
                $orders = $this->orderRepository->findBy(['id' => $editOrderId]);
            } else {
                $orders = [];
            }

            if (count($orders)) {
                /** @var $order Order */
                $tIds = $this->historyRepository->getOrderTransactionIds($orders);

                foreach ($orders as $order) {
                    $this->echoStr('受注ID:'.$order->getId().'...');

                    if (!array_key_exists($order->getId(), $tIds)) {
                        $this->echoStr('transaction id は存在しません');
                        continue;
                    }

                    $this->getauthoriBatch->setTimeOut();

                    try {
                        $psKey = $this->getauthoriBatch->Getauthor($config, $order, $tIds[$order->getId()], true);
                    } catch (\Exception $e) {
                        log_error($e->getMessage(), ['order_id' => $order->getId(), 'jaccs-logid' => 0]);

                        if ($e->getCode() == '999999999') {
                            $this->echoStr('アトディーネ通信失敗しました。詳細はログファイルをご確認ください。。');
                            $this->echoStr('バッチの実行を中断します');
                        } else {
                            $this->echoStr('エラーが発生しました。詳細はログファイルをご確認ください。。');
                            $this->echoStr('バッチの実行を中断します');
                        }
                        exit;
                    }

                    switch ($psKey) {
                        case 1:
                            $this->echoStr('=>取引OK');
                            break;
                        case 4:
                            $this->echoStr('=>取引キャンセル');
                            break;
                        case 2:
                            $ids = $this->session->get('jaccs_payment.admin.batch.error', []);
                            $ids[] = $order->getId();
                            $this->session->set('jaccs_payment.admin.batch.error', $ids);
                            $this->echoStr('=>取引エラー');
                            break;
                        case 3:
                            $ids = $this->session->get('jaccs_payment.admin.batch.hori', []);
                            $ids[] = $order->getId();
                            $this->session->set('jaccs_payment.admin.batch.hori', $ids);
                            $this->echoStr('=>保留');
                            break;
                        default:
                            break;
                    }
                }

                return $this->redirectToRoute('jaccs_payment_admin_batch');
            } else {
                $mess = sprintf('バッチの実行が完了しました<br />');

                $error = $this->session->get('jaccs_payment.admin.batch.error', []);
                if (count($error)) {
                    $mess .= 'アトディーネで入力された決済情報の項目にてエラーが発生しました。管理画面にて、受注ID:'.join(',', $error)."の受注のエラー情報をご確認後、受注情報の変更をお願いいたします。\n
詳細はログファイルをご確認ください。";
                }

                $hori = $this->session->get('jaccs_payment.admin.batch.hori', []);
                if (count($hori)) {
                    $mess .= 'アトディーネ審査保留、オーダーID:'.join(',', $hori);
                }

                return new Response(
                    '<html><body>'.$mess.'</body></html>'
                );
            }
        }

        return new Response(
            '<html><body>バッチタイプを手動に設定ください</body></html>'
        );
    }

    public function start()
    {
        ob_end_flush();
    }

    public function echoStr($str)
    {
        echo $str.'<br />';
        flush();
    }
}
