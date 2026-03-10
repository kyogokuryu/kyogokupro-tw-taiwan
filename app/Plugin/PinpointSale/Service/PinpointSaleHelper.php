<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/04
 */

namespace Plugin\PinpointSale\Service;


use Eccube\Common\EccubeConfig;
use Plugin\PinpointSale\Entity\Pinpoint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class PinpointSaleHelper
{

    /** @var ContainerInterface */
    private $container;

    /** @var EccubeConfig */
    private $eccubeService;

    public function __construct(
        ContainerInterface $container,
        EccubeConfig $eccubeConfig
    )
    {
        $this->container = $container;
        $this->eccubeService = $eccubeConfig;
    }

    /**
     * Request取得
     *
     * @return Request
     */
    private function getRequest()
    {
        /** @var Request $request */
        $request = $this->container
            ->get('request_stack')
            ->getMasterRequest();

        return $request;
    }

    /**
     * Request の ID取得
     *
     * @return mixed
     */
    public function getActiveId()
    {
        $id = $this->getRequest()
            ->attributes
            ->get('id');

        return $id;
    }

    /**
     * 管理画面での処理かチェック
     *
     * @return bool true: Admin
     * @throws \Exception
     */
    public function isAdminRoute()
    {

        /** @var Request $request */
        $request = $this->getRequest();

        if (!$request) {
            return false;
        }

        $path = $request->getPathInfo();
        $adminRoot = $this->eccubeService->get('eccube_admin_route');

        if (strpos($path, '/' . trim($adminRoot, '/')) === 0) {
            return true;
        }

        return false;
    }

    /**
     * 価格フック対象Route
     *
     * @return bool true:対象 false:対象外
     */
    public function isHookRoute()
    {
        // 該当画面は対象
        $hookRoute = [
            'homepage' => 1,
            'product_list' => 1,
            'product_detail' => 1,
            'product_add_cart' => 1,
            'cart' => 1,
            'cart_handle_item' => 1,
            'mypage_favorite' => 1,
            'mypage_order' => 1,
            'cbd' => 1,//新規作成ページを追加 20210727
        ];

        $request = $this->getRequest();

        if (!$request) {
            return true;
        }

        $route = $request->attributes->get('_route');
        //新規作成ページ(user_data)の対応 20210727
        if($route == 'user_data') $route = $request->attributes->get('route');

        if (isset($hookRoute[$route])) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Pinpoint $pinpointA
     * @param Pinpoint $pinpointB
     * @return int -1:繰り下げ, 0:変更なし, 1:繰り上げ
     */
    public function sortProductPinpoint($pinpointA, $pinpointB)
    {
        // 個別設定
        if (!$pinpointA->isSaleTypeCommon()
            && !$pinpointB->isSaleTypeCommon()) {

            // 単価or値引率
            // 開始期間判定 昇順
            if ($pinpointA->getStartTime() < $pinpointB->getStartTime()) {
                return -1;
            } elseif ($pinpointA->getStartTime() > $pinpointB->getStartTime()) {
                return 1;
            } else {
                return 0;
            }
        }

        if (!$pinpointA->isSaleTypeCommon()
            && $pinpointB->isSaleTypeCommon()) {
            // $pinpointA = 単価or値引率
            // $pinpointB = 共通
            return -1;
        } elseif ($pinpointA->isSaleTypeCommon()
            && !$pinpointB->isSaleTypeCommon()) {
            // $pinpointA = 共通
            // $pinpointB = 単価or値引率
            return 1;
        } else {
            // $pinpointA = 共通
            // $pinpointB = 共通
            if ($pinpointA->getSortNo() > $pinpointB->getSortNo()) {
                return -1;
            } elseif ($pinpointA->getSortNo() < $pinpointB->getSortNo()) {
                return 1;
            } else {
                return 0;
            }
        }
    }
}
