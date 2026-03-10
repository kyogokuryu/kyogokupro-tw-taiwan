<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/07/05
 */

namespace Plugin\KokokaraSelect\Service;


use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;

class OrderItemSortService
{

    /**
     * 受注データに紐づく商品を選択商品用にソート
     *
     * @param Order $order
     */
    public function sortOrder(Order $order)
    {
        $tmpOrderItems = [];

        /** @var OrderItem $orderItem */
        foreach ($order->getOrderItems() as $key => $orderItem) {
            $tmpOrderItems[$key] = $orderItem;
            $order->removeOrderItem($orderItem);
        }

        // 現状をクリア
        $order->getOrderItems()->clear();

        // ソート
        $tmpOrderItems = $this->sortOrderItem($tmpOrderItems);

        /** @var OrderItem $tmpOrderItem */
        foreach ($tmpOrderItems as $tmpOrderItem) {
            $order->addOrderItem($tmpOrderItem);
        }
    }

    /**
     * 配送データに紐づく商品を選択商品用にソート
     *
     * @param Shipping $shipping
     */
    public function sortShopping(Shipping $shipping)
    {
        $tmpOrderItems = [];

        /** @var OrderItem $orderItem */
        foreach ($shipping->getOrderItems() as $key => $orderItem) {
            $tmpOrderItems[$key] = $orderItem;
            $shipping->removeOrderItem($orderItem);
        }

        // 現状をクリア
        $shipping->getOrderItems()->clear();

        // ソート
        $tmpOrderItems = $this->sortOrderItem($tmpOrderItems);

        /** @var OrderItem $tmpOrderItem */
        foreach ($tmpOrderItems as $tmpOrderItem) {
            $shipping->addOrderItem($tmpOrderItem);
        }
    }

    /**
     * ShippingのOrderItemをソートした配列を返却
     *
     * @param Shipping $shipping
     * @return mixed
     */
    public function getSortShippingItems(Shipping $shipping)
    {
        $tmpOrderItems = [];

        /** @var OrderItem $orderItem */
        foreach ($shipping->getOrderItems() as $key => $orderItem) {
            $tmpOrderItems[$key] = $orderItem;
        }

        // ソート
        return $this->sortOrderItem($tmpOrderItems);
    }

    /**
     * OrderItemを選択商品用にソート
     *
     * @param $tmpOrderItems
     * @return mixed
     */
    private function sortOrderItem($tmpOrderItems)
    {
        // ソート
        uasort($tmpOrderItems, function (OrderItem $orderItemA, OrderItem $orderItemB) {

            // お届け先でのソート
            $shippingA = $orderItemA->getShipping();
            $shippingB = $orderItemB->getShipping();

            if ($shippingA && $shippingB) {
                if ($shippingA->getId() > $shippingB->getId()) {
                    return -1;
                } else if ($shippingA->getId() < $shippingB->getId()) {
                    return 1;
                }
            }

            if (($orderItemA->getKsOrderItemChildren()->count() == 0 && is_null($orderItemA->getKsOrderItemEx()))
                && ($orderItemB->getKsOrderItemChildren()->count() == 0 && is_null($orderItemB->getKsOrderItemEx()))) {

                // A,B共に通常商品
                if ($orderItemA->isProduct() && $orderItemB->isProduct()) {

                    $productA = $orderItemA->getProductClass()->getProduct();
                    $productB = $orderItemB->getProductClass()->getProduct();

                    if ($productA->getId() == $productB->getId()) {
                        return ($orderItemA->getProductClass()->getId() < $orderItemB->getProductClass()->getId() ? -1 : 1);
                    }

                    return ($productA->getID() < $productB->getId() ? -1 : 1);
                } else if ($orderItemA->isProduct()) {
                    return -1;
                } else {
                    return 1;
                }
            }

            if ($orderItemA->getKsOrderItemChildren()->count() == 0 && is_null($orderItemA->getKsOrderItemEx())) {

                // Aのみ通常商品
                if ($orderItemA->isProduct()) {
                    return -1;
                } else {
                    return 1;
                }
            }

            if ($orderItemB->getKsOrderItemChildren()->count() == 0 && is_null($orderItemB->getKsOrderItemEx())) {

                // Bのみ通常商品
                if ($orderItemB->isProduct()) {
                    return 1;
                } else {
                    return -1;
                }
            }

            // 親-親
            if ($orderItemA->getKsOrderItemChildren()->count() > 0 && $orderItemB->getKsOrderItemChildren()->count() > 0) {

                $productA = $orderItemA->getProductClass()->getProduct();
                $productB = $orderItemB->getProductClass()->getProduct();

                // A,B共に選択商品親
                return ($productA->getID() < $productB->getId() ? -1 : 1);
            }

            // 親-子
            if ($orderItemA->getKsOrderItemChildren()->count() > 0 && $orderItemB->isKsSelectItem()) {

                // A=親, B=子
                $parentOrderItem = $orderItemB->getKsOrderItemEx()->getParentOrderItem();

                $productA = $orderItemA->getProductClass()->getProduct();
                $productB = $parentOrderItem->getProductClass()->getProduct();

                if ($productA->getId() == $productB->getId()) {
                    return -1;
                }

                // AとBの親を比較
                return ($productA->getId() < $productB->getId() ? -1 : 1);
            }

            // 子-親
            if ($orderItemA->isKsSelectItem() && $orderItemB->getKsOrderItemChildren()->count() > 0) {

                // A=子, B=親
                $parentOrderItem = $orderItemA->getKsOrderItemEx()->getParentOrderItem();

                $productA = $parentOrderItem->getProductClass()->getProduct();
                $productB = $orderItemB->getProductClass()->getProduct();

                // Aの親とBを比較
                if ($productA->getId() == $productB->getId()) {
                    return 1;
                }

                return ($productA->getId() < $productB->getId() ? -1 : 1);
            }

            // 子-子
            if ($orderItemA->isKsSelectItem() && $orderItemB->isKsSelectItem()) {

                // A,B共に子
                $parentOrderItemA = $orderItemA->getKsOrderItemEx()->getParentOrderItem();
                $parentOrderItemB = $orderItemB->getKsOrderItemEx()->getParentOrderItem();

                $productA = $parentOrderItemA->getProductClass()->getProduct();
                $productB = $parentOrderItemB->getProductClass()->getProduct();

                // 商品IDでソート
                if ($productA->getId() < $productB->getId()) {
                    return -1;
                } else if ($productA->getId() > $productB->getId()) {
                    return 1;

                } else {

                    // 親商品が同一
                    $ksSelectItemA = $orderItemA->getKsOrderItemEx()->getKsSelectItem();
                    $ksSelectItemB = $orderItemB->getKsOrderItemEx()->getKsSelectItem();

                    if ($ksSelectItemA && $ksSelectItemB) {
                        // A,Bともに選択商品情報あり

                        // グループでソート
                        if ($orderItemA->getKsOrderItemEx()->getGroupId() < $orderItemB->getKsOrderItemEx()->getGroupId()) {
                            return -1;
                        } else if ($orderItemA->getKsOrderItemEx()->getGroupId() > $orderItemB->getKsOrderItemEx()->getGroupId()) {
                            return 1;
                        }

                        // 選択商品グループでソート
                        if (
                            $ksSelectItemA->getKsSelectItemGroup()->getSortNo() < $ksSelectItemB->getKsSelectItemGroup()->getSortNo()) {
                            return -1;
                        } else if ($ksSelectItemA->getKsSelectItemGroup()->getSortNo() > $ksSelectItemB->getKsSelectItemGroup()->getSortNo()) {
                            return 1;
                        }

                        // 選択商品の順番でソート
                        if ($ksSelectItemA->getSortNo() < $ksSelectItemB->getSortNo()) {
                            return -1;
                        } else if ($ksSelectItemA->getSortNo() > $ksSelectItemB->getSortNo()) {
                            return 1;
                        }
                    }

                    return ($orderItemA->getKsOrderItemEx()->getGroupId() < $orderItemB->getKsOrderItemEx()->getGroupId() ? -1 : 1);
                }
            }

            return 0;
        });

        return $tmpOrderItems;
    }
}
