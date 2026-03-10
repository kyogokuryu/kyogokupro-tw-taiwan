<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/29
 */

namespace Plugin\KokokaraSelect\Form\Type\Admin;


use Plugin\KokokaraSelect\Entity\KsOrderItemEx;
use Plugin\KokokaraSelect\Form\EventListener\KsOrderTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KsOrderItemExType extends AbstractType
{

    use KsOrderTypeTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('parentProductClassId', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('groupId', HiddenType::class, [

            ])
            ->add('ksSelectItemGroupId', HiddenType::class, [

            ])
            ->add('ksSelectItemId', HiddenType::class, [

            ]);

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, [
                $this->ksOrderTypeEventListener, "onKsOrderItemExTypePostSetData"
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KsOrderItemEx::class,
        ]);
    }
}
