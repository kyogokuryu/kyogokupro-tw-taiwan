<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/08
 */

namespace Plugin\PinpointSale\Form\Type;


use Plugin\PinpointSale\Entity\PinpointRepeat;
use Plugin\PinpointSale\Form\EventListener\PinpointRepeatTypeEventListener;
use Plugin\PinpointSale\Form\Transformer\CustomTimeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PinpointRepeatType extends AbstractType
{

    protected $formEventListener;

    public function __construct(
        PinpointRepeatTypeEventListener $formEventListener
    )
    {
        $this->formEventListener = $formEventListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('start_time', TimeType::class, [
                'label' => 'pinpoint_sale.pinpoint_repeat_start_time_label',
                'input' => 'datetime',
                'widget' => 'choice',
                'required' => false,
                'mapped' => true,
                'placeholder' => [
                    'hour' => '--', 'minute' => '--',
                ],
            ])
            ->add('end_time', TimeType::class, [
                'label' => 'pinpoint_sale.pinpoint_repeat_end_time_label',
                'input' => 'datetime',
                'widget' => 'choice',
                'required' => false,
                'mapped' => true,
                'placeholder' => [
                    'hour' => '--', 'minute' => '--',
                ],
            ])
            ->add('week_check', ChoiceType::class, [
                'label' => 'pinpoint_sale.pinpoint_repeat_week_title',
                'expanded' => true,
                'multiple' => true,
                'choices' => [
                    'pinpoint_sale.pinpoint_repeat_week0' => PinpointRepeat::WEEK_0,
                    'pinpoint_sale.pinpoint_repeat_week1' => PinpointRepeat::WEEK_1,
                    'pinpoint_sale.pinpoint_repeat_week2' => PinpointRepeat::WEEK_2,
                    'pinpoint_sale.pinpoint_repeat_week3' => PinpointRepeat::WEEK_3,
                    'pinpoint_sale.pinpoint_repeat_week4' => PinpointRepeat::WEEK_4,
                    'pinpoint_sale.pinpoint_repeat_week5' => PinpointRepeat::WEEK_5,
                    'pinpoint_sale.pinpoint_repeat_week6' => PinpointRepeat::WEEK_6,
                ],
                'mapped' => false,
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, [
                $this->formEventListener, 'postSetData'
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->formEventListener, 'postSubmit'
            ]);

        $builder
            ->get('start_time')
            ->addModelTransformer(new ReversedTransformer(
                new CustomTimeTransformer()
            ));

        $builder
            ->get('end_time')
            ->addModelTransformer(new ReversedTransformer(
                new CustomTimeTransformer()
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\PinpointSale\Entity\PinpointRepeat',
        ]);
    }
}
