<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/08/27
 */

namespace Plugin\KokokaraSelect\Form\Extension;


use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Form\Type\Admin\ShippingType;
use Plugin\KokokaraSelect\Service\OrderItemSortService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ShippingTypeExtension extends AbstractTypeExtension
{

    /** @var OrderItemSortService */
    protected $orderItemSortService;

    public function __construct(
        OrderItemSortService $orderItemSortService
    )
    {
        $this->orderItemSortService = $orderItemSortService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

                /** @var Shipping $data */
                $data = $event->getData();

                if ($data) {

                    $form = $event->getForm()->get('OrderItems');

                    $tmpForm = [];

                    /** @var Form $formItem */
                    foreach ($form as $key => $formItem) {
                        $tmpForm[] = $formItem;
                        $form->remove($key);
                    }

                    // OrderItemをソート
                    $sortOrderItems = $this->orderItemSortService->getSortShippingItems($data);

                    /** @var OrderItem $sortOrderItem */
                    foreach ($sortOrderItems as $sortOrderItem) {

                        foreach ($tmpForm as $key => $item) {

                            /** @var OrderItem $orderItemData */
                            $orderItemData = $item->getData();

                            if ($sortOrderItem->getId() == $orderItemData->getId()) {
                                $form->add($tmpForm[$key]);
                                unset($tmpForm[$key]);
                            }
                        }
                    }

                }
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {

                $form = $event->getForm();

                /** @var Shipping $data */
                $data = $event->getData();

                if ($data) {

                    /** @var Shipping $formData */
                    $formData = $form->getData();

                    if (!isset($data['OrderItems'])) {
                        return;
                    }

                    $orderItems = $data['OrderItems'];

                    // 選択商品マージ
                    $orderItems = $this->margeOrderItems($orderItems);
                    $data['OrderItems'] = $orderItems;

                    if ($formData) {
                        // 削除されている場合は関連情報削除
                        $orderItems = $this->deleteChainOrderItems($formData, $orderItems);
                        $data['OrderItems'] = $orderItems;
                    }

                    $event->setData($data);
                }

            });
    }

    /**
     * 選択商品SUBMITデータのマージ
     *
     * @param $orderItems
     * @return mixed
     */
    private function margeOrderItems($orderItems)
    {

        // 選択商品マージ
        foreach ($orderItems as $key => $orderItem) {

            if (empty($orderItem['ProductClass'])
                || isset($orderItem['KsOrderItemEx'])) {
                // 商品以外もしくは選択商品は処理対象外
                continue;
            }

            foreach ($orderItems as $key2 => $checkOrderItem) {

                if ($key >= $key2) {
                    continue;
                }

                if (empty($checkOrderItem['ProductClass'])
                    || isset($checkOrderItem['KsOrderItemEx'])) {
                    // 商品以外もしくは選択商品は処理対象外
                    continue;
                }

                if ($orderItem['ProductClass'] == $checkOrderItem['ProductClass']) {
                    // 同一商品は１つの商品にまとめる
                    $orderItems[$key]['quantity'] += $checkOrderItem['quantity'];
                    unset($orderItems[$key2]);
                }
            }
        }

        return $orderItems;
    }

    /**
     * 選択商品親が削除された場合、子も連動して削除
     *
     * @param Shipping $formData
     * @param array $orderItems
     * @return array
     */
    private function deleteChainOrderItems(Shipping $formData, array $orderItems)
    {
        $delTargets = [];

        // 削除対象判別
        /**
         * @var integer $key
         * @var OrderItem $orderItem
         */
        foreach ($formData->getOrderItems() as $key => $orderItem) {

            if ($orderItem->isProduct() && $orderItem->getKsProduct()) {

                if (!array_key_exists($key, $orderItems)) {
                    // 削除対象
                    $delTargets[] = $orderItem;
                }
            }

        }

        /** @var OrderItem $delTarget */
        foreach ($delTargets as $delTarget) {

            foreach ($formData->getOrderItems() as $key => $orderItem) {
                if ($orderItem->isKsSelectItem()) {
                    $parentOrderItemId = $orderItem->getKsOrderItemEx()->getParentOrderItem()->getId();
                    if ($delTarget->getId() == $parentOrderItemId) {
                        unset($orderItems[$key]);
                    }
                }
            }
        }

        return $orderItems;
    }

    public function getExtendedType()
    {
        return ShippingType::class;
    }
}
