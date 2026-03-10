<?php

namespace Plugin\Paidy4\Service;

use Eccube\Common\EccubeConfig;
use Plugin\Paidy4\Repository\ConfigRepository;

class PaidyRequestService
{
    /**
     * @var eccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var Config
     */
    protected $Config;

    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;

        $this->Config = $this->configRepository->get();
    }

    /**
     * PaidyAPI実行
     *
     * @param string $mode  処理名 : status_check, close, captures, refunds
     * @param array  $Order 受注データ
     * @param string $refundAmount 部分返金額
     *
     * @return multitype:boolean string |multitype:boolean mixed
     */
    function executePaidyAPI($mode, $Order, $refundAmount = null)
    {
        logs('paidy')->info('executePaidyAPI start mode=' . $mode . ', paidy_order_id=' . $Order['paidy_order_id'] .  ', refundAmount=' . $refundAmount);
        //$url = $this->eccubeConfig['paidy']['api_url'] . (string) $Order['paidy_order_id'];
        $paidy_order_id = empty($Order['paidy_order_id']) ? $_POST['paidy_order_id'] : $Order['paidy_order_id'];
        $url = $this->eccubeConfig['paidy']['api_url'] . (string) $paidy_order_id;
        $isPost = false;

        if($mode != 'status_check') {
            $url .= '/'.$mode;
            $isPost = true;
        }

        logs('paidy')->info('url=' . $url);

        $metaData = [];
        if($mode == 'refunds') {
            $metaData['capture_id'] = $Order['paidy_capture_id'];
            if($refundAmount != null) {
                $metaData['amount'] = $refundAmount;
            }
        }

        if($mode == 'captures' || $mode == 'refunds') {
            $metaData['metadata'] = array('Platform' => 'EC-CUBE ver4');
        }


        $headers = array(
                'Content-Type: application/json',
                'Paidy-Version: '.$this->eccubeConfig['paidy']['version'],
                'Authorization: Bearer '.$this->Config['secret_key'],
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

        if($isPost) {
            $data = $this->getJsonData($metaData);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        $result = curl_exec($ch);
        $info = curl_getinfo ($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $ApiResult = json_decode($result, true);
        if ($info['http_code'] == '200') {
            $ApiResult['executePaidyAPI'] = 'success';
            logs('paidy')->info('*** Paidy API success *** ' . var_export($ApiResult, true));
        } else {
            $ApiResult['executePaidyAPI'] = 'error';
            logs('paidy')->info('*** Paidy API error *** ' . var_export($ApiResult, true));
        }

        logs('paidy')->info('executePaidyAPI end');
        return $ApiResult;
    }

    function getJsonData($metaData) {
        if($metaData) {
            // マルチバイト文字をUNICODEにエンコードさせない
            $data = json_encode($metaData, JSON_UNESCAPED_UNICODE);
        } else {
            $data = "{}";
        }

        return $data;
    }
}
?>
