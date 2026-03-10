<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller\Admin\Order;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderPdf;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\OrderPdfType;
use Eccube\Form\Type\Admin\SearchOrderType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\Master\SexRepository;
use Eccube\Repository\OrderPdfRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\ProductStockRepository;
use Eccube\Service\CsvExportService;
use Eccube\Service\MailService;
use Eccube\Service\OrderPdfService;
use Eccube\Service\OrderStateMachine;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Customize\Entity\SalonafResult;//20220725 kikuzawa
use Customize\Repository\SalonafResultRepository;//20220725 kikuzawa

class OrderController extends AbstractController
{
    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var SexRepository
     */
    protected $sexRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var ProductStatusRepository
     */
    protected $productStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /** @var OrderPdfRepository */
    protected $orderPdfRepository;

    /**
     * @var ProductStockRepository
     */
    protected $productStockRepository;

    /** @var OrderPdfService */
    protected $orderPdfService;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var MailService
     */
    protected $mailService;

    //20220725 kikuzawa
    /**
     * @var SalonafResultRepository
     */
    protected $salonafResultRepository;

    /**
     * OrderController constructor.
     *
     * @param PurchaseFlow $orderPurchaseFlow
     * @param CsvExportService $csvExportService
     * @param CustomerRepository $customerRepository
     * @param PaymentRepository $paymentRepository
     * @param SexRepository $sexRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param ProductStockRepository $productStockRepository
     * @param OrderRepository $orderRepository
     * @param OrderPdfRepository $orderPdfRepository
     * @param ValidatorInterface $validator
     * @param OrderStateMachine $orderStateMachine ;
     */
    public function __construct(
        PurchaseFlow $orderPurchaseFlow,
        CsvExportService $csvExportService,
        CustomerRepository $customerRepository,
        PaymentRepository $paymentRepository,
        SexRepository $sexRepository,
        OrderStatusRepository $orderStatusRepository,
        PageMaxRepository $pageMaxRepository,
        ProductStatusRepository $productStatusRepository,
        ProductStockRepository $productStockRepository,
        OrderRepository $orderRepository,
        OrderPdfRepository $orderPdfRepository,
        ValidatorInterface $validator,
        OrderStateMachine $orderStateMachine,
        MailService $mailService,
        SalonafResultRepository $salonafResultRepository//20220725 kikuzawa
    ) {
        $this->purchaseFlow = $orderPurchaseFlow;
        $this->csvExportService = $csvExportService;
        $this->customerRepository = $customerRepository;
        $this->paymentRepository = $paymentRepository;
        $this->sexRepository = $sexRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->productStockRepository = $productStockRepository;
        $this->orderRepository = $orderRepository;
        $this->orderPdfRepository = $orderPdfRepository;
        $this->validator = $validator;
        $this->orderStateMachine = $orderStateMachine;
        $this->mailService = $mailService;
        $this->salonafResultRepository = $salonafResultRepository;//20220725 kikuzawa
    }

    /**
     * 受注一覧画面.
     *
     * - 検索条件, ページ番号, 表示件数はセッションに保持されます.
     * - クエリパラメータでresume=1が指定された場合、検索条件, ページ番号, 表示件数をセッションから復旧します.
     * - 各データの, セッションに保持するアクションは以下の通りです.
     *   - 検索ボタン押下時
     *      - 検索条件をセッションに保存します
     *      - ページ番号は1で初期化し、セッションに保存します。
     *   - 表示件数変更時
     *      - クエリパラメータpage_countをセッションに保存します。
     *      - ただし, mtb_page_maxと一致しない場合, eccube_default_page_countが保存されます.
     *   - ページング時
     *      - URLパラメータpage_noをセッションに保存します.
     *   - 初期表示
     *      - 検索条件は空配列, ページ番号は1で初期化し, セッションに保存します.
     *
     * @Route("/%eccube_admin_route%/order", name="admin_order")
     * @Route("/%eccube_admin_route%/order/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_order_page")
     * @Template("@admin/Order/index.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $builder = $this->formFactory
            ->createBuilder(SearchOrderType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        /**
         * ページの表示件数は, 以下の順に優先される.
         * - リクエストパラメータ
         * - セッション
         * - デフォルト値
         * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
         **/
        $page_count = $this->session->get('eccube.admin.order.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.admin.order.search.page_count', $page_count);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('eccube.admin.order.search', FormUtil::getViewData($searchForm));
                $this->session->set('eccube.admin.order.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                /*
                 * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                 */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('eccube.admin.order.search.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('eccube.admin.order.search.page_no', 1);
                }
                $viewData = $this->session->get('eccube.admin.order.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                $viewData = [];

                if ($statusId = (int) $request->get('order_status_id')) {
                    $viewData = ['status' => $statusId];
                }

                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('eccube.admin.order.search', $viewData);
                $this->session->set('eccube.admin.order.search.page_no', $page_no);
            }
        }

        $qb = $this->orderRepository->getQueryBuilderBySearchDataForAdmin($searchData);

        $event = new EventArgs(
            [
                'qb' => $qb,
                'searchData' => $searchData,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
            'OrderStatuses' => $this->orderStatusRepository->findBy([], ['sort_no' => 'ASC']),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/order/bulk_delete", name="admin_order_bulk_delete", methods={"POST"})
     */
    public function bulkDelete(Request $request)
    {
        $this->isTokenValid();
        $ids = $request->get('ids');
        foreach ($ids as $order_id) {
            $Order = $this->orderRepository
                ->find($order_id);
            if ($Order) {
                $this->entityManager->remove($Order);
                log_info('受注削除', [$Order->getId()]);
            }
        }

        $this->entityManager->flush();

        $this->addSuccess('admin.common.delete_complete', 'admin');

        return $this->redirect($this->generateUrl('admin_order', ['resume' => Constant::ENABLED]));
    }

    /**
     * 受注CSVの出力.
     *
     * @Route("/%eccube_admin_route%/order/export/order", name="admin_order_export_order")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportOrder(Request $request)
    {
        $filename = 'order_'.(new \DateTime())->format('YmdHis').'.csv';
        $response = $this->exportCsv($request, CsvType::CSV_TYPE_ORDER, $filename);
        log_info('受注CSV出力ファイル名', [$filename]);

        return $response;
    }

    /**
     * 配送CSVの出力.
     *
     * @Route("/%eccube_admin_route%/order/export/shipping", name="admin_order_export_shipping")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportShipping(Request $request)
    {
        $filename = 'shipping_'.(new \DateTime())->format('YmdHis').'.csv';
        $response = $this->exportCsv($request, CsvType::CSV_TYPE_SHIPPING, $filename);
        log_info('配送CSV出力ファイル名', [$filename]);

        return $response;
    }

    /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
     *
     * @return StreamedResponse
     */
    protected function exportCsv(Request $request, $csvTypeId, $fileName)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($request, $csvTypeId) {
            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType($csvTypeId);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getOrderQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData(function ($entity, $csvService) use ($request) {
                $Csvs = $csvService->getCsvs();

                $Order = $entity;
                $OrderItems = $Order->getOrderItems();

                foreach ($OrderItems as $OrderItem) {
                    $ExportCsvRow = new ExportCsvRow();

                    // CSV出力項目と合致するデータを取得.
                    foreach ($Csvs as $Csv) {
                        // 受注データを検索.
                        $ExportCsvRow->setData($csvService->getData($Csv, $Order));
                        if ($ExportCsvRow->isDataNull()) {
                            // 受注データにない場合は, 受注明細を検索.
                            $ExportCsvRow->setData($csvService->getData($Csv, $OrderItem));
                        }
                        if ($ExportCsvRow->isDataNull() && $Shipping = $OrderItem->getShipping()) {
                            // 受注明細データにない場合は, 出荷を検索.
                            $ExportCsvRow->setData($csvService->getData($Csv, $Shipping));
                        }

                        $event = new EventArgs(
                            [
                                'csvService' => $csvService,
                                'Csv' => $Csv,
                                'OrderItem' => $OrderItem,
                                'ExportCsvRow' => $ExportCsvRow,
                            ],
                            $request
                        );
                        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_CSV_EXPORT_ORDER, $event);

                        $ExportCsvRow->pushData();
                    }

                    //$row[] = number_format(memory_get_usage(true));
                    // 出力.
                    $csvService->fputcsv($ExportCsvRow->getRow());
// 複数行対策 2020.01.30
                    break;
                }
            });
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
        $response->send();

        return $response;
    }

    /**
     * Update to order status
     *
     * @Route("/%eccube_admin_route%/shipping/{id}/order_status", requirements={"id" = "\d+"}, name="admin_shipping_update_order_status", methods={"PUT"})
     *
     * @param Request $request
     * @param Shipping $Shipping
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateOrderStatus(Request $request, Shipping $Shipping)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $Order = $Shipping->getOrder();
        $OrderStatus = $this->entityManager->find(OrderStatus::class, $request->get('order_status'));

        if (!$OrderStatus) {
            return $this->json(['status' => 'NG'], 400);
        }

        $result = [];
        try {
            if ($Order->getOrderStatus()->getId() == $OrderStatus->getId()) {
                log_info('対応状況一括変更スキップ');
                $result = ['message' => trans('admin.order.skip_change_status', ['%name%' => $Shipping->getId()])];
            } else {
                if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                    if ($OrderStatus->getId() == OrderStatus::DELIVERED) {
                        if (!$Shipping->isShipped()) {
                            $Shipping->setShippingDate(new \DateTime());
                        }
                        $allShipped = true;
                        foreach ($Order->getShippings() as $Ship) {
                            if (!$Ship->isShipped()) {
                                $allShipped = false;
                                break;
                            }
                        }
                        if ($allShipped) {
                            $this->orderStateMachine->apply($Order, $OrderStatus);
                        }
                    } else {
                        $this->orderStateMachine->apply($Order, $OrderStatus);
                    }

                    if ($request->get('notificationMail')) { // for SimpleStatusUpdate
                        $this->mailService->sendShippingNotifyMail($Shipping);
                        $Shipping->setMailSendDate(new \DateTime());
                        $result['mail'] = true;
                    } else {
                        $result['mail'] = false;
                    }
                    // 対応中・キャンセルの更新時は商品在庫を増減させているので商品情報を更新
                    if ($OrderStatus->getId() == OrderStatus::IN_PROGRESS || $OrderStatus->getId() == OrderStatus::CANCEL) {
                        foreach ($Order->getOrderItems() as $OrderItem) {
                            $ProductClass = $OrderItem->getProductClass();
                            if ($OrderItem->isProduct() && !$ProductClass->isStockUnlimited()) {
                                $this->entityManager->flush($ProductClass);
                                $ProductStock = $this->productStockRepository->findOneBy(['ProductClass' => $ProductClass]);
                                $this->entityManager->flush($ProductStock);
                            }
                        }
                    }
                    $this->entityManager->flush($Order);
                    $this->entityManager->flush($Shipping);

                    // 会員の場合、購入回数、購入金額などを更新
                    if ($Customer = $Order->getCustomer()) {
                        $this->orderRepository->updateOrderSummary($Customer);
                        $this->entityManager->flush($Customer);
                    }

                    //3:注文取消し or 6:入金済み に変更の場合はアフィリエイトの報酬計算処理を行う 20220722 kikuzawa
                    if($Order['Customer']['salon_id'] && $Order['payment_date'] && ($OrderStatus->getId() == 3 || $OrderStatus->getId() == 6)){
                        $this->updateSalonafSales($Order);
                    }

                    //"入金済み"に変更した場合はギフトのポイント付与  20220817 kikuzawa
                    if($OrderStatus->getId() == 6){
                        $this->checkGift($Order);
                    }

                } else {
                    $from = $Order->getOrderStatus()->getName();
                    $to = $OrderStatus->getName();
                    $result = ['message' => trans('admin.order.failed_to_change_status', [
                        '%name%' => $Shipping->getId(),
                        '%from%' => $from,
                        '%to%' => $to,
                    ])];
                }

                log_info('対応状況一括変更処理完了', [$Order->getId()]);
            }
        } catch (\Exception $e) {
            log_error('予期しないエラーです', [$e->getMessage()]);

            return $this->json(['status' => 'NG'], 500);
        }

        return $this->json(array_merge(['status' => 'OK'], $result));
    }

    /**
     * Update to Tracking number.
     *
     * @Route("/%eccube_admin_route%/shipping/{id}/tracking_number", requirements={"id" = "\d+"}, name="admin_shipping_update_tracking_number", methods={"PUT"})
     *
     * @param Request $request
     * @param Shipping $shipping
     *
     * @return Response
     */
    public function updateTrackingNumber(Request $request, Shipping $shipping)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $trackingNumber = mb_convert_kana($request->get('tracking_number'), 'a', 'utf-8');
        /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $errors */
        $errors = $this->validator->validate(
            $trackingNumber,
            [
                new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                new Assert\Regex(
                    ['pattern' => '/^[0-9a-zA-Z-]+$/u', 'message' => trans('admin.order.tracking_number_error')]
                ),
            ]
        );

        if ($errors->count() != 0) {
            log_info('送り状番号入力チェックエラー');
            $messages = [];
            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return $this->json(['status' => 'NG', 'messages' => $messages], 400);
        }

        try {
            $shipping->setTrackingNumber($trackingNumber);
            $this->entityManager->flush($shipping);
            log_info('送り状番号変更処理完了', [$shipping->getId()]);
            $message = ['status' => 'OK', 'shipping_id' => $shipping->getId(), 'tracking_number' => $trackingNumber];

            return $this->json($message);
        } catch (\Exception $e) {
            log_error('予期しないエラー', [$e->getMessage()]);

            return $this->json(['status' => 'NG'], 500);
        }
    }

    /**
     * @Route("/%eccube_admin_route%/order/export/pdf", name="admin_order_export_pdf")
     * @Template("@admin/Order/order_pdf.twig")
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function exportPdf(Request $request)
    {
        // requestから出荷番号IDの一覧を取得する.
        $ids = $request->get('ids', []);

        if (count($ids) == 0) {
            $this->addError('admin.order.delivery_note_parameter_error', 'admin');
            log_info('The Order cannot found!');

            return $this->redirectToRoute('admin_order');
        }

        /** @var OrderPdf $OrderPdf */
        $OrderPdf = $this->orderPdfRepository->find($this->getUser());

        if (!$OrderPdf) {
            $OrderPdf = new OrderPdf();
            $OrderPdf
                ->setTitle(trans('admin.order.delivery_note_title__default'))
                ->setMessage1(trans('admin.order.delivery_note_message__default1'))
                ->setMessage2(trans('admin.order.delivery_note_message__default2'))
                ->setMessage3(trans('admin.order.delivery_note_message__default3'));
        }

        /**
         * @var FormBuilder
         */
        $builder = $this->formFactory->createBuilder(OrderPdfType::class, $OrderPdf);

        /* @var \Symfony\Component\Form\Form $form */
        $form = $builder->getForm();

        // Formへの設定
        $form->get('ids')->setData(implode(',', $ids));

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/order/export/pdf/download", name="admin_order_pdf_download")
     * @Template("@admin/Order/order_pdf.twig")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportPdfDownload(Request $request, OrderPdfService $orderPdfService, EntityManagerInterface $entityManager)
    {
        $id = $request->request->get('order_pdf')['ids'];

        $shipping = $entityManager->getRepository(Shipping::class)->find($id);

        $orderId = $shipping->getOrder()->getId();

        $order = $entityManager->getRepository(Order::class)->find($orderId);
        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        if (!$order->getInvoiceDate()) {
            $order->setInvoiceDate(new \DateTime());
        }

        if (!$order->getInvoiceNumber()) {
            $maxInvoiceNumber = $entityManager->createQueryBuilder()
                ->select('MAX(o.invoice_number)')
                ->from(Order::class, 'o')
                ->getQuery()
                ->getSingleScalarResult();

            $newInvoiceNumber = $maxInvoiceNumber ? ($maxInvoiceNumber + 1) : 1;
            $order->setInvoiceNumber($newInvoiceNumber);
        }

            $entityManager->flush();

        /**
         * @var FormBuilder
         */
        $builder = $this->formFactory->createBuilder(OrderPdfType::class);

        /* @var \Symfony\Component\Form\Form $form */
        $form = $builder->getForm();
        $form->handleRequest($request);

// 2020.06.10 update
//        // Validation
//         if (!$form->isValid()) {
//             log_info('The parameter is invalid!');
// 
//             return $this->render('@admin/Order/order_pdf.twig', [
//                 'form' => $form->createView(),
//             ]);
//         }
        // Validation
        if (!$form->isValid() && !isset($_POST['order_pdf'])) {
            log_info('The parameter is invalid!');

            return $this->render('@admin/Order/order_pdf.twig', [
                'form' => $form->createView(),
            ]);
        }
// 2020.06.10 update

// 2020.06.10 update
//         $arrData = $form->getData();
//         if ($form->isValid()) {
// 	        $arrData = $form->getData();
// 	        $downloadKind = $form->get('download_kind')->getData();
//         } else {
	        $arrData = $_POST['order_pdf'];
	        date_default_timezone_set('Asia/Tokyo');
	        $arrData['issue_date'] = new \DateTime($arrData['issue_date']);
	        $downloadKind = $arrData['download_kind'];
	        if($arrData['title'] == '1'){
	        	$arrData['title'] = trans('admin.order.delivery_note_title__default');
	        }else if($arrData['title'] == '2'){
	        	$arrData['title'] = trans('admin.order.delivery_note_title__default2') . "\n" . trans('admin.order.delivery_note_title__default2.2');
	        }else if($arrData['title'] == '3'){
	        	$arrData['title'] = trans('admin.order.delivery_note_title__default3');
	        }
//         }
// 2020.06.10 update

        // 購入情報からPDFを作成する
        $status = $orderPdfService->makePdf($arrData);

        // 異常終了した場合の処理
        if (!$status) {
            $this->addError('admin.order.export.pdf.download.failure', 'admin');
            log_info('Unable to create pdf files! Process have problems!');

            return $this->render('@admin/Order/order_pdf.twig', [
                'form' => $form->createView(),
            ]);
        }

        // ダウンロードする
        $response = new Response(
            $orderPdfService->outputPdf(),
            200,
            ['content-type' => 'application/pdf']
        );

        // レスポンスヘッダーにContent-Dispositionをセットし、ファイル名を指定
        if ($downloadKind == 1) {
// 2020.06.10 update
//             $response->headers->set('Content-Disposition', 'attachment; filename="'.$orderPdfService->getPdfFileName().'"');
	        $download_filename = '';
	        if($arrData['title'] == trans('admin.order.delivery_note_title__default')){
		        $download_filename = 'nouhinsyo-No'.$arrData['ids'].'.pdf';
	        }else if($arrData['title'] == trans('admin.order.delivery_note_title__default2')){
		        $download_filename = 'seikyusyo-No'.$arrData['ids'].'.pdf';
	        }else if($arrData['title'] == trans('admin.order.delivery_note_title__default3')){
		        $download_filename = 'ryousyusyo-No'.$arrData['ids'].'.pdf';
	        }else{
		        $download_filename = 'tyouhyou-No'.$arrData['ids'].'.pdf';
	        }
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$download_filename.'"');
// 2020.06.10 update
        } else {
            $response->headers->set('Content-Disposition', 'inline; filename="'.$orderPdfService->getPdfFileName().'"');
        }

// 2020.06.10 update
		$array_ids = explode(',',$arrData['ids']);
		foreach( $array_ids as $ids ){
			$shipping = $this->entityManager->getRepository('Eccube\Entity\Shipping')->find($ids);
			if($arrData['title'] == trans('admin.order.delivery_note_title__default')){
				$shipping->setOutPdf1(1);
			}else if($arrData['title'] == trans('admin.order.delivery_note_title__default2')){
				$shipping->setOutPdf2(1);
			}else if($arrData['title'] == trans('admin.order.delivery_note_title__default3')){
				$shipping->setOutPdf3(1);
			}
			$this->entityManager->flush($shipping);
		}
// 2020.06.10 update

        log_info('OrderPdf download success!', ['Order ID' => implode(',', $request->get('ids', []))]);

        $isDefault = isset($arrData['default']) ? $arrData['default'] : false;
        if ($isDefault) {
            // Save input to DB
            $arrData['admin'] = $this->getUser();
            $this->orderPdfRepository->save($arrData);
        }

        return $response;
    }

    //アフィリエイトの報酬計算 20220722 kikuzawa
    public function updateSalonafSales($Order)
    {
        $Customer = $Order['Customer'];

        //親の存在を確認
        $parent = $this->customerRepository->findOneBy(array('id' => $Customer['salon_id'],'Status' => 2));
        if($parent){
            $payment_date = new \DateTime($Order['payment_date']->format('Y-m'));
            $payment_date_end = clone $payment_date;
            $payment_date_end->modify('+1 month');

            // 購入処理中/決済処理中ステータスの受注を非表示にする.
            $this->entityManager
                ->getFilters()
                ->enable('incomplete_order_status_hidden');

            //売上の登録の有無を確認
            $SalonafResult = $this->salonafResultRepository->findOneBy(array('Customer' => $parent, 'month' => $payment_date));
            if($SalonafResult){
                //支払済の場合は再計算の対象から除外する
                if($SalonafResult['paid_flg']){
                    $SalonafResult = '';
                }
            }
            else{
                $SalonafResult = new SalonafResult();
            }

            if($SalonafResult){
                $qb = $this->entityManager->getRepository('Eccube\Entity\Customer')
                ->createQueryBuilder('c')->select('SUM(o.payment_total)')
                ->leftJoin('c.Orders', 'o')
                ->andWhere('c.Salon_id = :parent_id')
                ->andWhere('o.payment_date IS NOT NULL')//入金日がセットされてるものを対象
                ->andWhere('o.OrderStatus <> 3')//受注ステータスから"注文取り消し"を除外
                ->andWhere('o.payment_date >= :payment_date_start')//集計期間指定
                ->andWhere('o.payment_date < :payment_date_end')
                ->setParameter('parent_id', $parent['id'])
                ->setParameter('payment_date_start', $payment_date)
                ->setParameter('payment_date_end', $payment_date_end);

                $payment_total = $qb->getQuery()->getSingleResult();
                if($payment_total){
                    $ratio = $this->eccubeConfig['eccube_salonaf_ratio'];
                    $reward = round($payment_total[1]*$ratio);
                    $SalonafResult->setCustomer($parent);
                    $SalonafResult->setMonth($payment_date);
                    $SalonafResult->setSales($payment_total[1]);
                    $SalonafResult->setReward($reward);
                    $SalonafResult->setUpdateDate(new \DateTime());
                    $this->salonafResultRepository->save($SalonafResult);
                    $this->entityManager->flush();
                }
            }
        }
    }

    //購入商品にギフトが含まれるか確認してポイント付与とメール送信 20220817 kikuzawa
    public function checkGift($Order)
    {
        foreach ($Order->getOrderItems() as $Item) {
            if($Item['Product']['id']){
                $giftPoint = $Item['Product']->getValueData(2);
                if($giftPoint && $Item['OrderItemOptions']){
                    $giftPoint = $giftPoint * $Item['quantity'];
                    $email = '';
                    $sender = $Order['name01'].' '.$Order['name02'].'様';
                    $message = '';
                    foreach ($Item['OrderItemOptions'] as $option) {
                        $v = $option['OrderItemOptionCategories'][0]['value'];
                        switch ($option['option_id']) {
                            case 1:
                                $email = $v;
                                break;
                            case 2:
                                $sender = $v.'様';
                                break;
                            case 3:
                                $message = $v;
                                break;
                        }
                    }
                    $Customer = $this->customerRepository->findOneBy(array('email' => $email));
                    if($Customer){
                        //ポイント付与
                        $addPoint = $Customer->getPoint() + $giftPoint;
                        $Customer->setPoint($addPoint);
                        $this->entityManager->flush();

                        //メール送信
                        $this->mailService->sendGiftMail($Customer, $giftPoint, $sender, $message);
                    }
                }
            }
        }
    }

    /**
     * Update to Tracking number.
     *
     * @Route("/%eccube_admin_route%/order/{id}/invoice_number", requirements={"id" = "\d+"}, name="admin_order_update_invoice_number", methods={"PUT"})
     *
     * @param Request $request
     * @param Order $order
     *
     * @return Response
     */
    public function updateInvoiceNumber(Request $request, Order $order)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $invoiceNumber = mb_convert_kana($request->get('invoice_number'), 'a', 'utf-8');
        $errors = $this->validator->validate(
            $invoiceNumber,
            [
                new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                new Assert\Regex(
                    [
                        'pattern' => '/^[A-Za-z]{2}-\d{8}$/u',
                        'message' => trans('admin.order.tracking_number_error')
                    ]
                ),
            ]
        );

        if ($errors->count() != 0) {
            log_info('送り状番号入力チェックエラー');
            $messages = [];
    
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
    
            return $this->json(['status' => 'NG', 'messages' => $messages], 400);
        }

        try {
            $order->setInvoiceNumber($invoiceNumber);
            $this->entityManager->flush($order);
    
            log_info('送り状番号変更処理完了', [$order->getId()]);
            return $this->json([
                'status' => 'OK',
                'order_id' => $order->getId(),
                'invoice_number' => $invoiceNumber
            ]);
        } catch (\Exception $e) {
            log_error('予期しないエラー', [$e->getMessage()]); 
            return $this->json(['status' => 'NG'], 500);
        }
    }
}
