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

namespace Plugin\JaccsPayment\Controller;

use Doctrine\ORM\EntityManager;
use Eccube\Controller\AbstractShoppingController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class PaymentController extends AbstractShoppingController
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var PurchaseFlow
     */
    protected $shoppingPurchaseFlow;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var BaseInfo
     */
    protected $baseInfo;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * PaymentController constructor.
     * @param OrderRepository $orderRepository
     * @param CartService $cartService
     * @param EntityManager $entityManager
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param BaseInfoRepository $baseInfoRepository
     * @param OrderHelper $orderHelper
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __construct(
        OrderRepository $orderRepository,
        CartService $cartService,
        EntityManager $entityManager,
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        BaseInfoRepository $baseInfoRepository,
        OrderHelper $orderHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->cartService = $cartService;
        $this->entityManager = $entityManager;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->shoppingPurchaseFlow = $shoppingPurchaseFlow;
        $this->baseInfo = $baseInfoRepository->get();
        $this->orderHelper = $orderHelper;
    }

    /**
     * @Route("/jaccs/examination_complete", name="jaccs_examination_complete")
     * @Template("@JaccsPayment/default/jaccs_examination_complete.twig")
     */
    public function examinationComplete(Request $request)
    {
        $reData = ['BaseInfo' => $this->baseInfo];

        // 受注IDを取得
        $orderId = $this->session->get(OrderHelper::SESSION_ORDER_ID);

        if (empty($orderId)) {
            return $this->redirectToRoute('homepage');
        }

        /** @var $Order \Eccube\Entity\Order */
        $Order = $this->orderRepository->find($orderId);

        if (!$Order) {
            return $this->redirectToRoute('homepage');
        }

        $reData['Order'] = $Order;

        $this->cartService->clear();

        $this->entityManager->flush();

        // 受注に関連するセッションを削除

        log_info('[注文完了] 購入フローのセッションをクリアします. ');
        $this->orderHelper->removeSession();

        $hasNextCart = !empty($this->cartService->getCarts());

        log_info('[注文完了] 注文完了画面を表示しました. ', [$hasNextCart]);

        $reData['hasNextCart'] = $hasNextCart;

        return $reData;
    }

    /**
     * @Route("/jaccs/ng", name="jaccs_ng")
     * @Template("@JaccsPayment/default/jaccs_ng.twig")
     */
    public function jaccsNg(Request $request)
    {
        $reData = ['BaseInfo' => $this->baseInfo];

        // 受注IDを取得
        $orderId = $this->session->get(OrderHelper::SESSION_ORDER_ID);

        if (empty($orderId)) {
            return $this->redirectToRoute('homepage');
        }

        /** @var $Order Order */
        $Order = $this->orderRepository->find($orderId);
        if (!$Order) {
            $this->session->remove(OrderHelper::SESSION_ORDER_ID);

            return $this->redirectToRoute('homepage');
        }

        if ($Order->getOrderStatus()->getId() == OrderStatus::PENDING) {
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
            $Order->setOrderStatus($OrderStatus);
            $this->shoppingPurchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();
            $reData['Order'] = $Order;
        }

        $this->session->remove(OrderHelper::SESSION_ORDER_ID);

        return $reData;
    }

    /**
     * @Route("/jaccs/error", name="jaccs_error")
     * @Template("@JaccsPayment/default/jaccs_error.twig")
     */
    public function jaccsError(Request $request)
    {
        $reData = ['BaseInfo' => $this->baseInfo];

        // 受注IDを取得
        $orderId = $this->session->get(OrderHelper::SESSION_ORDER_ID);

        if (empty($orderId)) {
            return $this->redirectToRoute('homepage');
        }

        /** @var $Order Order */
        $Order = $this->orderRepository->find($orderId);

        if (!$Order) {
            $this->session->remove(OrderHelper::SESSION_ORDER_ID);

            return $reData;
        }

        if ($Order->getOrderStatus()->getId() == OrderStatus::PENDING) {
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
            $Order->setOrderStatus($OrderStatus);
            $this->shoppingPurchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();
            $reData['Order'] = $Order;
        }

        $this->session->remove(OrderHelper::SESSION_ORDER_ID);

        return $reData;
    }
}
