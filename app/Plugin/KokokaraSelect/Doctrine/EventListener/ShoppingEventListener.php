<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/13
 */

namespace Plugin\KokokaraSelect\Doctrine\EventListener;


use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\OrderItemSortService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ShoppingEventListener
{

    protected $container;

    /** @var KsOrderService */
    protected $ksOrderService;

    /** @var OrderItemSortService */
    protected $orderItemSortService;

    public function __construct(
        ContainerInterface $container,
        KsOrderService $ksOrderService,
        OrderItemSortService $orderItemSortService
    )
    {
        $this->container = $container;
        $this->ksOrderService = $ksOrderService;
        $this->orderItemSortService = $orderItemSortService;
    }

    public function postLoad(Shipping $shipping)
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getMasterRequest();
        $route = $request->attributes->get('_route');

        if ($route === 'admin_order_pdf_download') {
            /** @var OrderItem $orderItem */
            foreach ($shipping->getOrderItems() as $orderItem) {
                if ($orderItem->isKsSelectItem()) {
                    $shipping->removeOrderItem($orderItem);
                }
            }
        } else if ($route === 'admin_shipping_preview_notify_mail'
            || $route === 'admin_shipping_notify_mail') {

            // 出荷メール用にソート
            $this->orderItemSortService->sortShopping($shipping);
        }
    }
}
