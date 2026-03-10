<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/31
 */

namespace Plugin\KokokaraSelect\Form\Extension;


use Eccube\Form\Type\Admin\OrderType;
use Plugin\KokokaraSelect\Form\EventListener\KsOrderTypeTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

class OrderTypeExtension extends AbstractTypeExtension
{

    use KsOrderTypeTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, [
                $this->ksOrderTypeEventListener, "onOrderTypePostSetData"
            ], -10)
            ->addEventListener(FormEvents::PRE_SUBMIT, [
                $this->ksOrderTypeEventListener, "onOrderTypePreSubmit"
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->ksOrderTypeEventListener, "onOrderTypePostSubmit"
            ]);
    }


    public function getExtendedType()
    {
        return OrderType::class;
    }
}
