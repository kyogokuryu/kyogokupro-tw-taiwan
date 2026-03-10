<?php

namespace Plugin\Paidy4\Controller\Admin;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Util\FormUtil;
use Eccube\Util\StringUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\Paidy4\Form\Type\Admin\SearchPaymentType;
use Plugin\Paidy4\Service\Method\Paidy;
use Plugin\Paidy4\Service\PaidyOrderHelper;
use Plugin\Paidy4\Service\PaidyRequestService;
use Plugin\Paidy4\Util\PluginUtil as PaidyPluginUtil;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * 決済状況管理
 */
class PaymentStatusController extends AbstractController
{
    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var PaidyOrderHelper
     */
    protected $paidyOrderHelper;

    /**
     * @var PaidyRequestService
     */
    protected $paidyRequestService;

    /**
     * PaymentController constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PageMaxRepository $pageMaxRepository,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        PaidyOrderHelper $paidyOrderHelper,
        PaidyRequestService $paidyRequestService
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->paidyOrderHelper = $paidyOrderHelper;
        $this->paidyRequestService = $paidyRequestService;
    }

    /**
     * 決済状況一覧画面
     *
     * @Route("/%eccube_admin_route%/paidy4/payment_status", name="paidy4_admin_payment_status")
     * @Route("/%eccube_admin_route%/paidy4/payment_status/{page_no}", requirements={"page_no" = "\d+"}, name="paidy4_admin_payment_status_pageno")
     * @Template("@Paidy4/admin/Order/payment_status.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $searchForm = $this->createForm(SearchPaymentType::class);

        /**
         * ページの表示件数は, 以下の順に優先される.
         * - リクエストパラメータ
         * - セッション
         * - デフォルト値
         * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
         **/
        $page_count = $this->session->get('paidy.admin.payment_status.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('paidy.admin.payment_status.search.page_count', $page_count);
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
                $this->session->set('paidy.admin.payment_status.search', FormUtil::getViewData($searchForm));
                $this->session->set('paidy.admin.payment_status.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                    'status_color_code' => $this->eccubeConfig['paidy']['status_color_code'],
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                /*
                    * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                    */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('paidy.admin.payment_status.search.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('paidy.admin.payment_status.search.page_no', 1);
                }
                $viewData = $this->session->get('paidy.admin.payment_status.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                $searchData = [];

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('paidy.admin.payment_status.search', $searchData);
                $this->session->set('paidy.admin.payment_status.search.page_no', $page_no);
            }
        }

        $qb = $this->createQueryBuilder($searchData);
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
            'status_color_code' => $this->eccubeConfig['paidy']['status_color_code'],
        ];
    }

    /**
     * 決済処理.
     *
     * @Route("/%eccube_admin_route%/paidy4/payment_status/request_action/", name="paidy4_admin_payment_status_request", methods={"POST"})
     */
    public function requestAction(Request $request)
    {
        $requestType = $request->get('paidy_request');
        $requestMode = $request->get('paidy_mode');

        if (!isset($requestType)) {
            throw new BadRequestHttpException();
        }

        $this->isTokenValid();

        $count = 0;

        $refundAmount = null;

        if ($requestType == 'change_payment_status') {
            // 一括処理
            $ids = $request->get('ids');

        } elseif ($requestType == 'refunds') {
            // 個別返金
            $ids = $request->get('paidy_refund_order_id');
            $refundAll = $request->get('paidy_refund_all');
            if ($refundAll == 'false') {
                // 部分返金
                $refundAmount = $request->get('paidy_refund_amount');
            }
        }

        $Orders = $this->orderRepository->findBy(['id' => $ids]);
        list($count, $error) = $this->doChangePaymentStatus($Orders, $requestMode, $refundAmount);

        if($count) {
            // 処理結果表示用の処理名取得
            $arrPaidyBulkPaymentStatus = PaidyPluginUtil::getPaidyBulkPaymentStatus();
            if ($refundAmount != null) {
                $request = '部分返金処理（リファンド）';
            } else {
                $request = $arrPaidyBulkPaymentStatus[$requestMode];
            }

            $this->addSuccess(trans('paidy.admin.payment_status.bulk_action.success',[
                                        '%count%' => $count,
                                        '%request%' => $request,
                                    ]),'admin');
        }

        if($error) {
            $this->addError(implode(', ', $error),'admin');
        }

        return $this->redirectToRoute('paidy4_admin_payment_status_pageno', ['resume' => Constant::ENABLED]);
    }

    private function createQueryBuilder(array $searchData)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')
            ->from(Order::class, 'o')
            ->orderBy('o.update_date', 'DESC')
            ->addOrderBy('o.id', 'DESC');

        // Paidyのみ
        $Payment = $this->paymentRepository->findOneBy(array('method_class' => Paidy::class));
        $qb->andWhere('o.Payment = :Payment')
            ->setParameter('Payment', $Payment);

        // multi
        if (isset($searchData['multi']) && StringUtil::isNotBlank($searchData['multi'])) {
            $multi = preg_match('/^\d{0,10}$/', $searchData['multi']) ? $searchData['multi'] : null;
            $where = <<< __WHERE__
o.id = :multi
    OR o.name01 LIKE :likemulti
    OR o.name02 LIKE :likemulti
    OR o.kana01 LIKE :likemulti
    OR o.kana02 LIKE :likemulti
    OR o.company_name LIKE :likemulti
    OR o.order_no LIKE :likemulti
    OR o.email LIKE :likemulti
    OR o.phone_number LIKE :likemulti
    OR o.paidy_order_id LIKE :likemulti
__WHERE__;
            $qb
                ->andWhere($where)
                ->setParameter('multi', $multi)
                ->setParameter('likemulti', '%' . $searchData['multi'] . '%');
        }

        // 対応状況
        $filterStatus = false;
        if (!empty($searchData['OrderStatuses']) && count($searchData['OrderStatuses']) > 0) {
            $qb->andWhere($qb->expr()->in('o.OrderStatus', ':OrderStatuses'))
                ->setParameter('OrderStatuses', $searchData['OrderStatuses']);
                $filterStatus = true;
        }
        if (!$filterStatus) {
            // 対応状況のフィルターがない場合は, 購入処理中, 決済処理中は検索対象から除外
            $qb->andWhere($qb->expr()->notIn('o.OrderStatus', ':OrderStatuses'))
                ->setParameter('OrderStatuses', [OrderStatus::PROCESSING, OrderStatus::PENDING]);
        }

        // Paidy決済状況
        if (!empty($searchData['paidy_status']) && count($searchData['paidy_status']) > 0) {
            $addWhere = '';
            $searchPaidyStatusData = [];

            foreach ($searchData['paidy_status'] as $paidyStatus) {
                $where = '';

                // オーソリ, クローズ
                if ($paidyStatus == 'authorized' || $paidyStatus == 'closed') {
                    // ステータスが「authorized」「closed」の受注
                    if (count($searchPaidyStatusData) == 0) {
                        // 1回目のみwhere句生成
                        $where .= $qb->expr()->in('o.paidy_status', ':paidy_status');
                    }
                    $searchPaidyStatusData[] = $paidyStatus;

                // 売上確定（キャプチャー）済み
                } elseif ($paidyStatus == 'captured') {
                    // paidy_capture_idがある受注
                    $where .= 'o.paidy_capture_id IS NOT NULL';
                }

                if ($where != '') {
                    if ($addWhere != '') $addWhere .= ' OR ';
                    $addWhere .= $where;
                } 
            }

            if (count($searchPaidyStatusData) > 0) {
                $qb->andWhere($addWhere)
                    ->setParameter('paidy_status', $searchPaidyStatusData);
            } else {
                $qb->andWhere($addWhere);
            }
        }

        return $qb;
    }

    /**
     * 決済処理
     *
     * @param array  $Orders       対象受注
     * @param string $requestMode  決済モード
     * @param string $refundAmount 部分返金額
     * @return array エラーメッセージ
     */
    public function doChangePaymentStatus($Orders, $requestMode, $refundAmount = null)
    {
        $listErrorMsg = [];
        $count = 0;

        $listErrorMsg = $this->checkErrorOrder($Orders, $requestMode, $refundAmount);
        if($listErrorMsg) {
            return [$count, $listErrorMsg];
        }

        foreach ($Orders as $Order) {

            $errMsg = '';

            $ApiResult = $this->paidyRequestService->executePaidyAPI($requestMode, $Order, $refundAmount);
            if ($ApiResult['executePaidyAPI'] == 'error') {
                $errMsg = $this->getErrorMessageByErrorCode($ApiResult['code']);
            }

            if ($errMsg == '') {
                // Paidy受注更新
                $update_flg = $this->paidyOrderHelper->updatePaidyOrder($Order, $ApiResult);

                if ($update_flg) {
                    // 受注更新日時を設定
                    $Order->setUpdateDate(new \DateTime());
                    $this->entityManager->flush();
                }
            }

            if($errMsg != ''){
                $listErrorMsg[] = '※ 決済操作エラー が発生しました。';
                $listErrorMsg[] = '注文番号 ' . $Order['order_no'];
                $listErrorMsg[] = $errMsg;
                return [$count, $listErrorMsg];
            }

            $count++;
        }

        return [$count, $listErrorMsg];
    }

    /**
     * エラーチェック
     *
     * @param array  $Orders      対象受注ID
     * @param string $requestMode 一括操作
     * @return array エラーメッセージ
     */
    public function checkErrorOrder($Orders, $requestMode, $refundAmount = null)
    {
        $listErrMsg = [];

        foreach ($Orders as $Order) {
            $orderId = $Order->getId();
            $msg = '注文番号 ' . $orderId . ' : ';
            // 支払方法がPaidy決済かどうか判定する
            if ($Order->getPayment()->getMethodClass() != Paidy::class) {
                $errMsgList = $this->eccubeConfig['paidy']['plugin_error']['payment_method_error'];
            } else {
                $statusErrMsg = '';
                if (($requestMode == 'captures' || $requestMode == 'close') && $Order->getPaidyStatus() != 'authorized') {
                    // キャプチャー、クローズはオーソリ状態の受注のみ
                    $statusErrMsg = $this->eccubeConfig['paidy']['plugin_error']['capture_colse_failed'];
                } elseif ($requestMode == 'captures' && $Order->getPaidyExpireDate() < new \DateTime()) {
                    // キャプチャー時有効期限チェック
                    $statusErrMsg = $this->eccubeConfig['paidy']['plugin_error']['capture_expired'];
                }

                if ($requestMode == 'refunds') {
                    if ($Order->getPaidyCaptureId() == null) {
                        // 返金はキャプチャー済みの受注のみ
                        $statusErrMsg = $this->eccubeConfig['paidy']['plugin_error']['close_failed'];
                    } elseif ($Order->getPaidyPaymentTotal() - $Order->getPaidyRefundTotal() <= 0) {
                        // 返金はキャプチャー済みの受注のみ
                        $statusErrMsg = $this->eccubeConfig['paidy']['plugin_error']['all_refunded'];
                    } elseif ($refundAmount != null) {
                        if ($Order->getPaidyPaymentTotal() - $Order->getPaidyRefundTotal() < $refundAmount) {
                            // 返金額が請求額を上回る場合
                            $statusErrMsg = $this->eccubeConfig['paidy']['plugin_error']['refund_amount_exceeded'];
                        } elseif ($refundAmount != null && !is_numeric($refundAmount)) {
                            // 部分返金額が数値でない場合
                            $statusErrMsg = $this->eccubeConfig['paidy']['plugin_error']['numeric_error'];
                        }
                    }
                }

                if ($statusErrMsg != '') {
                    $arrPaidyBulkPaymentStatus = PaidyPluginUtil::getPaidyBulkPaymentStatus();
                    $listErrMsg[] = $msg . $arrPaidyBulkPaymentStatus[$requestMode] . '処理は、' . $statusErrMsg;
                }
            }
        }

        return $listErrMsg;
    }

    /**
     * エラーコードに対応するエラーメッセージを取得する
     *
     * @param string $errorCode エラーコード
     * @return string エラーメッセージ
     */
    public function getErrorMessageByErrorCode($errorCode)
    {
        $errMsgList = $this->eccubeConfig['paidy']['api_error'];
        $message = isset($errMsgList[$errorCode]) ? $errMsgList[$errorCode] : "";
        return "エラーコード : ".$errorCode .", エラー説明 : ". $message;
    }
}
