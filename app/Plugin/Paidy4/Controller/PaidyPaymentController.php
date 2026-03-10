<?php

namespace Plugin\Paidy4\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Exception\ShoppingException;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\Paidy4\Repository\ConfigRepository;
use Plugin\Paidy4\Service\PaidyOrderHelper;
use Plugin\Paidy4\Service\PaidyRequestService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Paidyの決済用ページを処理する.
 */
class PaidyPaymentController extends AbstractController
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PaidyOrderHelper
     */
    protected $paidyOrderHelper;

    /**
     * @var PaidyRequestService
     */
    protected $paidyRequestService;

    public function __construct(
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        CartService $cartService,
        MailService $mailService,
        ConfigRepository $configRepository,
        PaidyOrderHelper $paidyOrderHelper,
        PaidyRequestService $paidyRequestService
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->paidyOrderHelper = $paidyOrderHelper;
        $this->paidyRequestService = $paidyRequestService;
        $this->configRepository = $configRepository;
        $this->Config = $this->configRepository->get();
    }

    /**
     * Paidy決済画面を表示する.
     *
     * @Route("/paidy_confirm", name="paidy_confirm")
     * @Template("@Paidy4/default/Shopping/paidy_confirm.twig")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function paidyConfirm(Request $request)
    {
        // 画面遷移妥当性チェック
        if ($request->get('order_id') !== null) {
            $orderId = $request->get('order_id');
            $Order   = $this->getOrderById($orderId);
            if($this->checkCompletedPaidyOrder($Order)){
                logs('paidy')->info("処理中の受注（ID：{$orderId}）が既にPaidy決済完了したため、購入エラー画面に遷移する.");
                throw new NotFoundHttpException();
            }
        }

        logs('paidy')->info('paidyConfirm start.');

        // 受注の存在チェック.
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->getPendingOrder($preOrderId);
        if (!$Order) {
            logs('paidy')->info('[リダイレクト] 購入処理中の受注が存在しません.');

            return $this->redirectToRoute('shopping_error');
        }

        return [
            'plugin_config'   => $this->Config,
            'checkout_js'     => $this->eccubeConfig['paidy']['checkout_js'],
            'target_order_id' => $Order->getId(),
            'paidyOrderData'  => $this->paidyOrderHelper->getPaidyOrderData($Order),
        ];

        logs('paidy')->info('paidyConfirm end.');
    }

    /**
     * Paidy決済画面でrejectedとなり、カートに戻る場合
     *
     * @Route("/paidy_back", name="paidy_back")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function paidyBack(Request $request)
    {
        logs('paidy')->info('paidyBack start.');

        if ($request->get('order_id') !== null) {
            $orderId = $request->get('order_id');
            $Order = $this->getOrderById($orderId);
        }

        if (!$Order) {
            throw new NotFoundHttpException();
        }

        // 受注ステータスを購入処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
        $Order->setOrderStatus($OrderStatus);

        // purchaseFlow::rollbackを呼び出し, 購入処理をロールバックする.
        // PAIDY-59対応（PAIDY-48の影響で受注完了時のみ在庫引とポイントを減算するようになったためロールバック不要）
        //$this->purchaseFlow->rollback($Order, new PurchaseContext());
        //$this->entityManager->flush();

        logs('paidy')->info('paidyBack end.');

        return $this->redirectToRoute('cart');
    }

    /**
     * 完了画面へ遷移する.
     *
     * @Route("/paidy_complete", name="paidy_complete")
     *
     * @return RedirectResponse
     */
    public function paidyComplete(Request $request)
    {
        logs('paidy')->info('paidyComplete start.');

        if ($request->get('order_id') !== null) {
            $orderId = $request->get('order_id');
            $Order = $this->getOrderById($orderId);
        }

        if (!$Order) {
            throw new NotFoundHttpException();
        }

        $callBackData = $_POST;

        // 対応状況が「決済処理中」かどうか確認
        $isPending = $this->checkPendingOrder($Order);
        if (!$isPending) {
            logs('paidy')->info('[注文処理] 購入処理済み', [$Order->getId()]);
        } else {

            logs('paidy')->info('[注文処理] 購入処理開始', [$Order->getId()]);

            $error_message = $this->completePaidyOrder($Order, $callBackData);

            // 在庫・ポイント整合性チェックに失敗した場合
            if ($error_message != '') {
                // Paidy受注キャンセル処理
                $this->cancelPaidyOrder($Order);
                // エラー画面へ
                $this->addError($error_message);
                return $this->redirectToRoute('shopping_error');
            }

            logs('paidy')->info('[注文処理] 購入処理完了', [$Order->getId()]);
        }

        // カートを削除する
        logs('paidy')->info('[注文処理] カートをクリアします.', [$Order->getId()]);
        $this->cartService->clear();

        // FIXME 完了画面を表示するため, 受注IDをセッションに保持する
        $this->session->set('eccube.front.shopping.order.id', $Order->getId());

        logs('paidy')->info('[注文処理] 購入完了画面へ遷移します.', [$Order->getId()]);
        logs('paidy')->info('paidyComplete end.');

        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * webhook処理を行う.
     *
     * @Route("/paidy_webhook", name="paidy_webhook", methods={"POST"})
     *
     * @return Response
     */
    public function paidyWebhook(Request $request)
    {
        logs('paidy')->info('*****  paidyWebhook start.  ***** ');

        // Webhookリクエスト元IP確認
        $webhookRequestIp = str_replace(array("\r\n", "\r", "\n"), "\n", $this->Config->getWebhookRequestIp());
        $arrWebhookRequestIp = array_filter(explode("\n", $webhookRequestIp));
        if (!in_array($_SERVER['REMOTE_ADDR'], $arrWebhookRequestIp)) {
            http_response_code( 403 ) ;
            logs('paidy')->info('[エラー] Webhookリクエスト元IPエラー. ip : '.$_SERVER['REMOTE_ADDR']);
            return new Response(0);
        }

        // Paidyより受信したPOSTを取得
        $json = file_get_contents('php://input');
        $arrWebhookResponce = [];
        if($json) {
            // JSONを配列に変換
            $arrWebhookResponce = json_decode($json, true);
        }
        logs('paidy')->info(' << '.$arrWebhookResponce['status'].' >> webhook responce => '.var_export($arrWebhookResponce, true));

        switch ($arrWebhookResponce['status']) {
            case "authorize_success":
                $Order = $this->getOrderById($arrWebhookResponce['order_ref']);

                if (empty($Order)) {
                    logs('paidy')->info('[WEBHOOK] 受注情報が見つかりません.', [$orderId]);
                } else {
                    // 対応状況が「決済処理中」かどうか確認
                    $isPending = $this->checkPendingOrder($Order);
                    if (!$isPending) {
                        logs('paidy')->info('[WEBHOOK] 購入処理済み', [$Order->getId()]);
                    } else {
                        logs('paidy')->info('[注文処理] 購入処理開始', [$Order->getId()]);

                        $OrderInfo['paidy_order_id'] = $arrWebhookResponce['payment_id'];
                        $this->completePaidyOrder($Order, $OrderInfo);

                        logs('paidy')->info('[注文処理] 購入処理完了', [$Order->getId()]);
                    }
                }
                break;

            case "capture_success":
            case "refund_success":
            case "update_success":
            case "close_success":
                break;
        }

        // Paidyに200 HTTP Statusコードをレスポンス
        http_response_code( 200 ) ;

        logs('paidy')->info('*****  paidyWebhook end.  *****');

        return new Response(1);
    }

    /**
     * Paidy決済画面でrejectedとなり、カートに戻る場合
     *
     * @Route("/paidy_cancel", name="paidy_cancel")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function paidyCancel(Request $request)
    {
        logs('paidy')->info('paidyCancel start.');

        if ($request->get('order_id') !== null) {
            $orderId = $request->get('order_id');
            $Order = $this->getOrderById($orderId);
        }

        if (!$Order) {
            throw new NotFoundHttpException();
        }

        // 受注ステータスを購入処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
        $Order->setOrderStatus($OrderStatus);
        $this->entityManager->flush();

        logs('paidy')->info('paidyCancel end.');

        return $this->redirectToRoute('shopping');
    }

    /**
     * 決済処理中の受注を取得する.
     *
     * @param null|string $preOrderId
     *
     * @return null|Order
     */
    public function getPendingOrder($preOrderId = null)
    {
        if (null === $preOrderId) {
            return null;
        }

        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);

        return $this->orderRepository->findOneBy([
            'pre_order_id' => $preOrderId,
            'OrderStatus' => $OrderStatus,
        ]);
    }

    /**
     * 受注をIDで検索する.
     *
     * @param $orderId
     *
     * @return Order
     */
    private function getOrderById($orderId)
    {
        /** @var Order $Order */
        $Order = $this->orderRepository->findOneBy([
            'id' => $orderId,
        ]);

        return $Order;
    }

    /**
     * 対応状況が「決済処理中」かどうか確認する.
     *
     * @param Order  $Order
     *
     * @return bool
     */
    private function checkPendingOrder($Order) {
        $OrderStatus = $Order->getOrderStatus()->getId();
        if ($OrderStatus == OrderStatus::PENDING) {
            return true;
        }
        return false;
    }

    /**
     * 受注完了処理を行う.
     *
     * @param  Order  $Order
     *         array  $callBackData
     * @return string エラーメッセージ
     */
    private function completePaidyOrder($Order, $callBackData)
    {
        // 決済データの検証(verify payment)
        $ApiResult = $this->paidyRequestService->executePaidyAPI('status_check', $callBackData);
        if ($Order['payment_total'] != $ApiResult['amount']) {
            logs('paidy')->info('[エラー] verify payment error. ec[payment_total] : '.$Order['payment_total'].' / paidy[amount] : '.$ApiResult['amount'], [$Order->getId()]);
            return '購入金額の整合性チェックエラーが発生しました。';
        }

        // Paidy特有のデータをセット
        $this->paidyOrderHelper->setPaidyOrder($Order, $ApiResult);

        // -- ポイントチェック
        $error_message = $this->checkUsePoint($Order);
        if ($error_message != '') {
            logs('paidy')->info('[エラー] checkUsePoint: '.$error_message, [$Order->getId()]);
            return $error_message;
        }

        try {
            // purchaseFlow::prepareを呼び出し, 購入処理を進める.
            // -- 在庫チェック
            $this->purchaseFlow->prepare($Order, new PurchaseContext());
        } catch (ShoppingException $e) {
            logs('paidy')->info('[エラー] purchaseFlow::prepare ShoppingException: '.$e->getMessage(), [$Order->getId()]);
            return $e->getMessage();
        } catch (\Exception $e) {
            logs('paidy')->info('[エラー] purchaseFlow::prepare Exception:'.$e->getMessage(), [$Order->getId()]);
            return $e->getMessage();
        }

        // 即時売上の場合
        if($this->Config['charge_type'] == $this->eccubeConfig['paidy']['charge_type']['capture']) {
            // Authorize + Capture
            $ApiResult = $this->paidyRequestService->executePaidyAPI('captures', $Order);
            $this->paidyOrderHelper->updatePaidyOrder($Order, $ApiResult);
        }

        // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
        $this->purchaseFlow->commit($Order, new PurchaseContext());

        // メール送信
        logs('paidy')->info('[注文処理] 注文メールの送信を行います.', [$Order->getId()]);
        $this->sendPaidyOrderMail($Order);

        $this->entityManager->flush();

        return '';
    }

    /**
     * 注文完了メールを送信する.
     *
     * @param Order  $Order
     */
    private function sendPaidyOrderMail($Order) {
        // 特記事項を設定 
        if (!is_null($this->Config->getMailDescriptionText())) {
            $Order->appendCompleteMailMessage($this->Config->getMailTitle()."：".$this->Config->getMailDescriptionText());
        }
        $this->mailService->sendOrderMail($Order);
    }

    /**
     * 既にPaidy決済完了したがとうかのチェック
     */
    private function checkCompletedPaidyOrder($Order){
        $OrderStatus = $Order->getOrderStatus()->getId();
        $PaidyStatus = $Order->getPaidyStatus();
        // 対応状況が「新規受付」、かつ、PAYDYステータスが「authorized」
        if ($OrderStatus == OrderStatus::NEW && $PaidyStatus == 'authorized') {
            return true;
        }
        return false;
    }

    /**
     * Paidy受注キャンセル処理
     */
    private function cancelPaidyOrder($Order)
    {
        logs('paidy')->info('[注文処理] Paidy受注をキャンセルします', [$Order->getId()]);
        $this->paidyRequestService->executePaidyAPI('close', $Order);
        $this->entityManager->rollback();
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

    /**
     * 利用ポイントが所持ポイントを上回らないかチェック
     *
     * @param  Order  $Order
     * @return string エラーメッセージ
     */
    public function checkUsePoint($Order)
    {
        if ($Order->getCustomer()) {
            $context = new PurchaseContext();
            if ($context->getOriginHolder()) {
                $fromUsePoint = $context->getOriginHolder()->getUsePoint();
            } else {
                $fromUsePoint = 0;
            }
            $toUsePoint = $Order->getUsePoint();
            $diffUsePoint = $toUsePoint - $fromUsePoint;
            // 所有ポイント < 新規利用ポイント
            $Customer = $Order->getCustomer();
            if ($Customer->getPoint() < $diffUsePoint) {
                return trans('purchase_flow.over_customer_point');
            }
        }
        return '';
    }
}
