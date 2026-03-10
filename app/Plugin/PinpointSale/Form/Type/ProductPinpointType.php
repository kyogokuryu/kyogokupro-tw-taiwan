<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/09/08
 */

namespace Plugin\PinpointSale\Form\Type;


use Plugin\PinpointSale\Form\EventListener\ProductPinpointTypeEventListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductPinpointType extends AbstractType
{

    private $formEventListener;

    public function __construct(ProductPinpointTypeEventListener $formEventListener)
    {
        $this->formEventListener = $formEventListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // タイムセール設定での利用時 true
        $pinpointSaleCommon = $options['pinpoint_sale_common'];

        $builder
            ->add('pinpoint', PinpointType::class, [
                'pinpoint_sale_common' => $pinpointSaleCommon,
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, [
                $this->formEventListener, "postSetData"
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->formEventListener, "postSubmit"
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\PinpointSale\Entity\ProductPinpoint',
            'pinpoint_sale_common' => false,
        ]);
    }
}
