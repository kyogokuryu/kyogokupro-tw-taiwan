<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/29
 */

namespace Plugin\KokokaraSelect\Doctrine\EventListener;


use Eccube\Entity\Order;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\OrderItemSortService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class OrderEventListener
{

    /** @var ContainerInterface */
    protected $container;

    /** @var KsService */
    protected $ksService;

    /** @var KsOrderService */
    protected $ksOrderService;

    /** @var OrderItemSortService */
    protected $orderItemSortService;

    public function __construct(
        ContainerInterface $container,
        KsService $ksService,
        KsOrderService $ksOrderService,
        OrderItemSortService $orderItemSortService
    )
    {
        $this->container = $container;
        $this->ksService = $ksService;
        $this->ksOrderService = $ksOrderService;
        $this->orderItemSortService = $orderItemSortService;
    }

    public function postLoad(Order $order)
    {
        $request = $this->container->get('request_stack')->getMasterRequest();
        if($request && $request->attributes){
            $route = $request->attributes->get('_route');

            if ($route === 'admin_shipping_edit') {
                return;
            }
        }

        // 注文明細ソート
        $this->orderItemSortService->sortOrder($order);
    }

    /**
     * 受注作成時
     *
     * @param Order $order
     */
    public function prePersist(Order $order)
    {

        if (!$this->ksService->isAdminRoute()) {

            // 選択商品の明細追加
            $this->ksOrderService->addKsOrderItem($order);

            // 注文明細ソート
            $this->orderItemSortService->sortOrder($order);
        }

    }

}
