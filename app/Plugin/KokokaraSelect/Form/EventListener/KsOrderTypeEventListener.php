<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/29
 */

namespace Plugin\KokokaraSelect\Form\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductClassRepository;
use Plugin\KokokaraSelect\Entity\KsOrderItemEx;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\KsService;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class KsOrderTypeEventListener
{

    use ConfigHelperTrait;

    /** @var KsService */
    protected $ksService;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Session */
    protected $session;

    /** @var KsOrderService */
    protected $ksOrderService;

    protected $deleteOrderItems;

    /** @var ProductClassRepository */
    protected $productClassRepository;

    public function __construct(
        KsService $ksService,
        EntityManagerInterface $entityManager,
        KsOrderService $ksOrderService,
        SessionInterface $session,
        ProductClassRepository $productClassRepository
    )
    {
        $this->ksService = $ksService;
        $this->entityManager = $entityManager;
        $this->ksOrderService = $ksOrderService;
        $this->session = $session;
        $this->productClassRepository = $productClassRepository;
    }

    /**
     * 受注の商品明細Formをソート
     *
     * @param FormEvent $event
     */
    public function onOrderTypePostSetData(FormEvent $event)
    {
        /** @var Order $data */
        $data = $event->getData();

        $form = $event->getForm()->get('OrderItems');

        $tmpForm = [];

        /** @var Form $formItem */
        foreach ($form as $key => $formItem) {
            $tmpForm[] = $formItem;
            $form->remove($key);
        }

        // Itemをソート
        foreach ($data->getOrderItems() as $orderItem) {

            foreach ($tmpForm as $key => $item) {
                /** @var OrderItem $orderItemData */
                $orderItemData = $item->getData();

                if ($orderItem->getId() == $orderItemData->getId()) {
                    $form->add($tmpForm[$key]);
                    unset($tmpForm[$key]);
                }
            }
        }

        foreach ($tmpForm as $item) {
            $form->add($item);
        }
    }

    /**
     * OrderType PRE_SUBMIT
     *
     * @param FormEvent $event
     */
    public function onOrderTypePreSubmit(FormEvent $event)
    {

        $form = $event->getForm();

        /** @var Order $formData */
        $formData = $form->getData();

        /** @var Order $data */
        $data = $event->getData();

        $this->deleteOrderItems = [];

        // 削除されるProductClassを特定
        if (isset($data['OrderItems'])) {
            foreach ($data['OrderItems'] as $orderItem) {

                if ($orderItem['order_item_type'] != OrderItemType::PRODUCT) {
                    // 商品以外は無視
                    continue;
                }

                if (!empty($orderItem['KsOrderItemEx']['parentProductClassId'])) {
                    // 選択商品も無視
                    continue;
                }

                $checkList[$orderItem['ProductClass']] = true;
            }
        }

        if ($formData->getShippings()->count() == 1) {

            $margeCheck = [];

            // 選択商品の重複追加チェック
            if (isset($data['OrderItems'])) {
                $orderItems = $data['OrderItems'];

                foreach ($orderItems as $key => $orderItem) {

                    $targetProductClassId = $orderItem['ProductClass'];

                    if (empty($targetProductClassId)) {
                        continue;
                    }

                    // 商品情報取得
                    /** @var ProductClass $targetProductClass */
                    $targetProductClass = $this->productClassRepository->find($targetProductClassId);

                    if ($this->ksService->isKsProduct($targetProductClass)) {
                        // 選択商品親
                        if (!isset($margeCheck[$targetProductClassId])) {
                            $margeCheck[$targetProductClassId] = $key;
                        } else {
                            // 既に存在するものはマージ
                            if ($this->ksService->isDirectSelectKsProduct($targetProductClass)) {
                                // 数量変更
                                $upTarget = $margeCheck[$targetProductClassId];
                                $orderItems[$upTarget]['quantity'] += 1;
                            } else {
                                // 警告する
                                $message = trans('kokokara_select.admin.order.item.add.alert', ['%kokokara_select%' => $this->getKokokaraSelectName()]);
                                $this->session->getFlashBag()->add('eccube.admin.warning', $message);
                            }

                            unset($orderItems[$key]);
                        }
                    }
                }

                $data['OrderItems'] = $orderItems;
            }
        }

        /** @var OrderItem $item */
        foreach ($formData->getItems() as $item) {

            if (!$item->isProduct()) {
                continue;
            }

            if ($item->isKsSelectItem()) {
                continue;
            }

            $productClass = $item->getProductClass();
            if ($this->ksService->isKsProduct($productClass)) {

                // 選択商品
                $productClassId = $productClass->getId();
                if (!isset($checkList[$productClassId])) {
                    // 親情報が削除される
                    // 紐づく子もカット
                    if (isset($data['OrderItems'])) {
                        foreach ($data['OrderItems'] as $key => $orderItem) {

                            if (empty($orderItem['KsOrderItemEx']['parentProductClassId'])) {
                                continue;
                            }

                            if ($orderItem['KsOrderItemEx']['parentProductClassId'] == $productClassId) {
                                // 削除対象
                                unset($data['OrderItems'][$key]);
                            }
                        }
                    }

                }
            }
        }

        $event->setData($data);
    }

    /**
     * OrderType POST_SUBMIT
     *
     * @param FormEvent $event
     */
    public function onOrderTypePostSubmit(FormEvent $event)
    {

        $form = $event->getForm();

        /** @var Order $data */
        $data = $form->getData();

        $parentOrderItems = [];

        /** @var OrderItem $orderItem */
        foreach ($data->getOrderItems() as $orderItem) {

            if (!$orderItem->isProduct()) {
                continue;
            }

            // 選択商品親情報取得
            if ($this->ksService->isKsProduct($orderItem)) {
                $orderItem->clearKsOrderItemChildren();
                $parentOrderItems[$orderItem->getProductClass()->getId()] = $orderItem;
            }

        }

        // groupIdの振り直し
        $groupIdList = [];

        if (!empty($parentOrderItems)) {

            /** @var FormInterface $orderItemForm */
            foreach ($form->get('OrderItems') as $orderItemForm) {

                // 選択商品子と親の結びつけ
                /** @var OrderItem $orderItem */
                $orderItem = $orderItemForm->getData();

                if ($orderItem->isKsSelectItem()) {

                    $parentProductClassId = $orderItemForm->get('KsOrderItemEx')->get('parentProductClassId')->getData();

                    $orderItem->setOrder($form->getData());

                    /** @var OrderItem $parentOrderItem */
                    $parentOrderItem = $parentOrderItems[$parentProductClassId];

                    $ksOrderItemEx = $orderItem->getKsOrderItemEx();
                    $ksOrderItemEx->setParentOrderItem($parentOrderItem);
                    $ksOrderItemEx->setOrderItem($orderItem);

                    $orderItem->setKsOrderItemEx($ksOrderItemEx);

                    $nowGroupId = $ksOrderItemEx->getGroupId();

                    if (isset($groupIdList[$parentProductClassId])) {
                        // GroupId判別
                        if (isset($groupIdList[$parentProductClassId][$nowGroupId])) {
                            // 設定値を採用
                            $ksOrderItemEx->setGroupId($groupIdList[$parentProductClassId][$nowGroupId]);
                        } else {
                            // 新たに採番
                            $newIndex = count($groupIdList[$parentProductClassId]) + 1;
                            $groupIdList[$parentProductClassId][$nowGroupId] = $newIndex;
                        }
                        $ksOrderItemEx->setGroupId($groupIdList[$parentProductClassId][$nowGroupId]);
                    } else {
                        $groupIdList[$parentProductClassId][$nowGroupId] = 1;
                    }

                    $ksOrderItemEx->setGroupId($groupIdList[$parentProductClassId][$nowGroupId]);

                    $parentOrderItem->addKsOrderItemChildren($ksOrderItemEx);
                    $parentProductClass = $parentOrderItem->getProductClass();

                    if (!$this->ksService->isDirectSelectKsProduct($parentProductClass)) {

                        // 選択商品のグループ数量で親数量を更新する
                        if ($parentOrderItem->getQuantity() != $ksOrderItemEx->getGroupId()) {
                            $parentOrderItem->setQuantity($ksOrderItemEx->getGroupId());
                        }
                    }

                }

            }
        }

        // データチェック
        $validResults = $this->ksOrderService->validOrderItem($data);

        foreach ($form->get('OrderItems') as $orderItemForm) {

            /** @var OrderItem $orderItem */
            $orderItem = $orderItemForm->getData();

            if (!$orderItem->isProduct()) continue;

            if (isset($validResults[$orderItem->getProductClass()->getId()])) {

                $validResult = $validResults[$orderItem->getProductClass()->getId()];

                if (!$validResult['valid']) {
                    // エラー
                    // 構成エラー
                    if (isset($validResult['ksSelectItemStructureErrors'])) {
                        foreach ($validResult['ksSelectItemStructureErrors'] as $ksSelectItemStructureError) {
                            if (isset($ksSelectItemStructureError['NG'])) {
                                $message = trans('kokokara_select.admin.order.selectItem.ng_structure', [
                                    '%product%' => $orderItem->getProductClass()->getProduct()->getName()
                                ]);
                                // 警告する
                                $this->session->getFlashBag()->add('eccube.admin.warning', $message);
                            }
                        }
                    }

                    // グループエラー
                    if (isset($validResult['ksSelectItemGroupErrors'])) {
                        foreach ($validResult['ksSelectItemGroupErrors'] as $groupId => $ksSelectItemGroupError) {

                            if (isset($ksSelectItemGroupError['NGQuantity'])) {
                                $message = trans('kokokara_select.admin.order.selectItem.ng_quantity', ['%groupId%' => $groupId]);
                                $orderItemForm->get('KsOrderItemEx')->addError(new FormError($message));
                            }
                        }
                    }

                    // 在庫チェックはPurchaseFlowで実施
                }
            }
        }
    }

    /**
     * OrderItemType POST_SUBMIT
     *
     * @param FormEvent $event
     */
    public function onOrderItemTypePostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var OrderItem $data */
        $data = $form->getData();

        if (!$data->isProduct()) {
            // 拡張設定クリア
            $data->setKsOrderItemEx(null);
            return;
        }

        if ($this->ksService->isKsProduct($data)) {
            // 拡張設定クリア
            $data->setKsOrderItemEx(null);
            return;
        }

        $parentProductClassId = $form->get('KsOrderItemEx')->get('parentProductClassId')->getData();
        if (empty($parentProductClassId)) {
            $data->setKsOrderItemEx(null);
            return;
        }

        $ksOrderItemEx = $data->getKsOrderItemEx();
        if (empty($ksOrderItemEx->getProductName())) {
            $ksOrderItemEx->setProductName($data->getProductClass()->getProduct()->getName());
        }
    }

    /**
     * OrderItemType POST_SET_DATA
     *
     * @param FormEvent $event
     */
    public function onKsOrderItemExTypePostSetData(FormEvent $event)
    {
        /** @var KsOrderItemEx $data */
        $data = $event->getData();

        if ($data) {
            // 連動用初期値設定
            $form = $event->getForm();
            $form->get('parentProductClassId')->setData($data->getParentOrderItem()->getProductClass()->getId());
        }
    }
}
