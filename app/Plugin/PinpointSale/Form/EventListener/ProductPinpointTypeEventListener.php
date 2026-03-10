<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/09/08
 */

namespace Plugin\PinpointSale\Form\EventListener;


use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\ProductPinpoint;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class ProductPinpointTypeEventListener
{

    public function postSetData(FormEvent $event)
    {
        /** @var FormInterface $form */
        $form = $event->getForm();

        /** @var ProductPinpoint $data */
        $data = $event->getData();

        $pinpointForm = $form->get('pinpoint');

        if (is_null($data)) {
            // 初期データなし
            $pinpointForm->setData(new Pinpoint());
        } else {
            $pinpoint = $data->getPinpoint();
            $pinpointForm->setData($pinpoint);

            // セール種類設定
            $pinpointForm->get('sale_type')->setData($pinpoint->getSaleType());
        }
    }

    public function postSubmit(FormEvent $event)
    {

    }
}
