<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Service\OrderHelper;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GetProductInformationFromOrderService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonOrderNoService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RequestDirectCardPaymentService
{
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;
    /**
     * @var object|null
     */
    private $Config;
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var GetProductInformationFromOrderService
     */
    private $getProductInformationFromOrderService;
    /**
     * @var GmoEpsilonOrderNoService
     */
    private $gmoEpsilonOrderNoService;
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    public function __construct(
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        ConfigRepository $configRepository,
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        EccubeConfig $eccubeConfig,
        GetProductInformationFromOrderService $getProductInformationFromOrderService,
        GmoEpsilonOrderNoService $gmoEpsilonOrderNoService,
        SessionInterface $session,
        OrderHelper $orderHelper
    ) {
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->Config = $configRepository->get();
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->eccubeConfig = $eccubeConfig;
        $this->getProductInformationFromOrderService = $getProductInformationFromOrderService;
        $this->gmoEpsilonOrderNoService = $gmoEpsilonOrderNoService;
        $this->session = $session;
        $this->orderHelper = $orderHelper;
    }

    public function handle($Order, $processCode, $stCode, $route, $token)
    {
        $status = 'NG';
        $Customer = $Order->getCustomer();
        if (is_null($Customer)) {
            $user_id = 'non_customer';
            /** @var Customer $Customer */
            $Customer = $this->orderHelper->getNonMember('eccube.front.shopping.nonmember');
        } else {
            $user_id = $Customer->getId();
        }
        $itemInfo = $this->getProductInformationFromOrderService->handle($Order);
        $orderNumber = $this->gmoEpsilonOrderNoService->create($Order->getId());
        $parameters = [
            'contract_code' => $this->Config->getContractCode(),
            'user_id' => $user_id,
            'user_name' => $Customer->getName01().$Customer->getName02(),
            'user_mail_add' => $Customer->getEmail(),
            'order_number' => $orderNumber,
            'item_name' => $itemInfo['item_name'],
            'item_code' => $itemInfo['item_code'],
            'item_price' => $Order->getPaymentTotal(),
            'st_code' => $stCode,
            'mission_code' => 1,
            'process_code' => $processCode,
            'memo1' => $route,
            'memo2' => 'EC-CUBE4_'.date('YmdHis'),
            'xml' => '1',
            'user_agent' => array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : null,
            'tds_check_code' => 1, // 3DSフラグ / NULL or 1 ：通常処理　（初回）
            'token' => $token,
            'keitai' => 0, // 3DS-keitai / 購入者の利用端末が携帯の場合必須 / NULL　or　0　：PC　or　1：携帯 / *3DS処理が携帯電話では利用不可のため通知が必要となります
            'security_check' => 1,
        ];

        $xmlResponse = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl(
                'direct_card_payment'),
            $parameters
        );

        $message = $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_DETAIL');

        $errCode = $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_CODE');
        if (empty($errCode)) {
            $message = '正常終了';
            $status = 'OK';
        } else {
            logs('gmo_epsilon')->error('ERR_CODE = '.$errCode);
            logs('gmo_epsilon')->error('ERR_DETAIL = '.$message);
        }

        $arrReturn = [
            'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RESULT'),
            'err_code' => (int) $errCode,
            'message' => $message,
            'status' => $status,
            'trans_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'TRANS_CODE'),
            'order_number' => $orderNumber,
        ];

        // 0：決済NG   1：決済OK  5：3DS処理（カード会社に接続必要）    9：システムエラー（パラメータ不足、不正等）
        $result = $arrReturn['result'];
        if ($result == $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['3ds']) {
            // 3DS処理時カード会社への接続用URLURLエンコードされています。
            $arrReturn['acsurl'] = $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ACSURL');
            // 3DS認証処理に必要な項目です。
            $arrReturn['pareq'] = $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'PAREQ');
        }

        return $arrReturn;
    }
}
