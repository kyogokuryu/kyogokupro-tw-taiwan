<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/26
 */

namespace Plugin\KokokaraSelect\Doctrine\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\OrderItem;
use Plugin\KokokaraSelect\Entity\KsOrderItemEx;
use Plugin\KokokaraSelect\Service\KsOrderItemService;
use Plugin\KokokaraSelect\Service\KsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class OrderItemEventListener
{

    /** @var ContainerInterface */
    protected $container;

    /** @var KsOrderItemService */
    protected $ksOrderItemService;

    /** @var KsService */
    protected $ksService;

    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(
        ContainerInterface $container,
        KsOrderItemService $ksOrderItemService,
        KsService $ksService,
        EntityManagerInterface $entityManager
    )
    {
        $this->container = $container;
        $this->ksOrderItemService = $ksOrderItemService;
        $this->ksService = $ksService;
        $this->entityManager = $entityManager;
    }

    public function postLoad(OrderItem $orderItem)
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getMasterRequest();
        if($request && $request->attributes){
            $route = $request->attributes->get('_route');

            // 管理画面の場合
            if ($this->ksService->isAdminRoute()) {

                if ($route === 'admin_order_pdf_download') {

                    if ($orderItem->isProduct()
                        && $this->ksService->isKsProduct($orderItem)) {

                        $ksProduct = $orderItem->getKsProduct();

                        // 選択商品親へ構成情報を設定する
                        $names = [];

                        if ($ksProduct->isDirectSelect()) {
                            // 固定選択商品
                            $names = $this->ksOrderItemService->getViewOrderItemDirectName($orderItem->getKsOrderItemChildren());
                        } else {
                            // 通常選択商品
                            /** @var KsOrderItemEx $ksOrderItemChild */
                            foreach ($orderItem->getKsOrderItemChildren() as $ksOrderItemChild) {
                                $names[] = $this->ksOrderItemService->getViewOrderItemName($ksOrderItemChild->getOrderItem(), " ");
                            }
                        }

                        $productName = $orderItem->getProductName();
                        $orderItem->setKsTmpProductName($productName);
                        $orderItem
                            ->setProductName($productName . "\n" . implode("\n", $names));
                    }

                }

                if ($orderItem->isKsSelectItem()) {

                    // 選択商品の名称は動的に生成する
                    // ※保持は購入時点の情報
                    if ($route === 'admin_order_new' || $route === 'admin_order_edit'
                        || $route === 'admin_shipping_preview_notify_mail' || $route === 'admin_shipping_notify_mail') {
                        $viewName = $this->ksOrderItemService->getViewOrderItemName($orderItem, "");
                    } else {
                        $viewName = $this->ksOrderItemService->getViewOrderItemName($orderItem);
                    }

                    $orderItem->setProductName($viewName);

                    if ($route === 'admin_order_mail') {
                        // 受注メール送信時は商品扱いとしない
                        return;
                    }

                    /** @var OrderItemType $productItemType */
                    $productItemType = $this->ksOrderItemService->getProductOrderItemType();;
                    $orderItem
                        ->setOrderItemType($productItemType);
                }

            } else {

                if ($orderItem->isKsSelectItem()) {

                    // 選択商品の名称は動的に生成する
                    // ※保持は購入時点の情報
                    $viewName = $this->ksOrderItemService->getViewOrderItemName($orderItem, "");
                    $orderItem->setProductName($viewName);

                    if ($route === 'mypage_order') {

                        // 再注文の場合は商品判定とならないよう回避
                        $orderItem
                            ->setKsTmpProduct($orderItem->getProduct())
                            ->setKsTmpProductClass($orderItem->getProductClass());

                        $orderItem
                            ->setProduct(null)
                            ->setProductClass(null);
                    }
                }
            }
        }
    }

    public function prePersist(OrderItem $orderItem)
    {

        // 管理画面の場合
        if ($this->ksService->isAdminRoute()) {
            if ($orderItem->isKsSelectItem()) {
                $ksOrderItemType = $this->ksOrderItemService->getKsOrderItemType();
                $orderItem
                    ->setOrderItemType($ksOrderItemType);
            }
        }
    }

    public function postPersist(OrderItem $orderItem)
    {
        $this->prePersist($orderItem);

        if ($this->ksService->isAdminRoute()
            && $orderItem->isKsSelectItem()) {

            // persist後の変更反映
            $unitOfWork = $this->entityManager->getUnitOfWork();
            $unitOfWork->computeChangeSets();
        }
    }

    public function preUpdate(OrderItem $orderItem)
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getMasterRequest();
        $route = $request->attributes->get('_route');

        // 管理画面の場合
        if ($this->ksService->isAdminRoute()) {

            if ($route === 'admin_order_pdf_download') {
                $orderItem
                    ->setProductName($orderItem->getKsTmpProductName());

            } else {

                if ($orderItem->isKsSelectItem()) {
                    $ksOrderItemType = $this->ksOrderItemService->getKsOrderItemType();
                    $orderItem
                        ->setOrderItemType($ksOrderItemType);
                }
            }

        } else {

            if ($route === 'mypage_order') {
                if ($orderItem->isKsSelectItem()) {
                    // 元に戻す
                    $orderItem
                        ->setProduct($orderItem->getKsTmpProduct())
                        ->setProductClass($orderItem->getKsTmpProductClass());
                }
            }
        }

    }
}
