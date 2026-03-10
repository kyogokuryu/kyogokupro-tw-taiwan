<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/29
 */

namespace Plugin\KokokaraSelect\Form\Extension;


use Eccube\Form\Type\Admin\OrderItemType;
use Plugin\KokokaraSelect\Form\EventListener\KsOrderTypeTrait;
use Plugin\KokokaraSelect\Form\Type\Admin\KsOrderItemExType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

class OrderItemTypeExtension extends AbstractTypeExtension
{

    use KsOrderTypeTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('KsOrderItemEx', KsOrderItemExType::class, [
                'error_bubbling' => false,
            ]);

        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->ksOrderTypeEventListener, "onOrderItemTypePostSubmit"
            ]);

    }

    public function getExtendedType()
    {
        return OrderItemType::class;
    }
}
