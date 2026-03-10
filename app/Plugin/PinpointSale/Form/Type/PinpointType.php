<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/04
 */

namespace Plugin\PinpointSale\Form\Type;


use Doctrine\ORM\EntityRepository;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\PriceType;
use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\PinpointRepeat;
use Plugin\PinpointSale\Form\EventListener\PinpointTypeEventListener;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PinpointType extends AbstractType
{

    /** @var PinpointTypeEventListener */
    private $formEventListener;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    public function __construct(
        PinpointTypeEventListener $formEventListener,
        EccubeConfig $eccubeConfig
    )
    {
        $this->formEventListener = $formEventListener;
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // タイムセール設定での利用時 true
        $pinpointSaleCommon = $options['pinpoint_sale_common'];

        $rateConstraintEx = null;

        if ($pinpointSaleCommon) {

            // 共通設定用
            $builder
                ->add('sale_type', HiddenType::class, [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'data' => Pinpoint::TYPE_COMMON
                ])
                ->add('name', TextType::class, [
                    'label' => 'pinpoint_sale.admin.pinpoint_sale_common.form.name',
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                    ],
                ]);

            $rateConstraintEx = new Assert\NotBlank();

            $customDateTimeBlankMode = CustomDateTimeType::BLANK_MODE_ALL;

        } else {

            $builder
                ->add('sale_type', ChoiceType::class, [
                    'label' => 'pinpoint_sale.admin.pinpoint_area_title',
                    'choices' => [
                        'pinpoint_sale.type_price' => Pinpoint::TYPE_PRICE,
                        'pinpoint_sale.type_rate' => Pinpoint::TYPE_RATE,
                        'pinpoint_sale.type_common' => Pinpoint::TYPE_COMMON,
                    ],
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'required' => true,
                    'choice_attr' => function ($choiceValue, $key, $value) {
                        return [
                            'class' => 'pinpoint_type_radio',
                        ];
                    },
                    'mapped' => false,
                ]);

            // 共通設定
            $builder
                ->add('sale_rate_common', EntityType::class, [
                    'class' => 'Plugin\PinpointSale\Entity\Pinpoint',
                    'choice_label' => 'viewName',
                    'mapped' => false,
                    'query_builder' => function (EntityRepository $er) {
                        return $er
                            ->createQueryBuilder('p')
                            ->where('p.saleType = :saleType')
                            ->setParameter('saleType', Pinpoint::TYPE_COMMON)
                            ->orderBy('p.sortNo', 'DESC');
                    }
                ]);

            $customDateTimeBlankMode = CustomDateTimeType::BLANK_MODE_NONE;
        }

        $rateConstraints = [
            new Assert\Regex([
                'pattern' => "/^\d+$/u",
                'message' => 'form_error.numeric_only',
            ]),
            new Assert\Range([
                'min' => 1,
                'max' => 99,
            ]),
        ];

        if ($rateConstraintEx) {
            $rateConstraints[] = $rateConstraintEx;
        }

        $builder
            ->add('salePrice', PriceType::class, [
                'required' => false,
            ])
            ->add('saleRate', NumberType::class, [
                'label' => 'pinpoint_sale.type_rate',
                'required' => false,
                'constraints' => $rateConstraints,
                'attr' => [
                    'placeholder' => 'pinpoint_sale.type_rate'
                ]
            ])
            ->add('start_time', CustomDateTimeType::class, [
                'label' => 'pinpoint_sale.admin.pinpoint_sale_common.form.start_time',
                'mapped' => true,
                'required' => false,
                'blank_mode' => $customDateTimeBlankMode,
            ])
            ->add('end_time', CustomDateTimeType::class, [
                'label' => 'pinpoint_sale.admin.pinpoint_sale_common.form.end_time',
                'mapped' => true,
                'required' => false,
                'blank_mode' => $customDateTimeBlankMode,
            ])
            ->add('del_flg', HiddenType::class, [
                'mapped' => false,
                'data' => 0
            ]);

        // 繰り返し設定
        $builder
            ->add('repeat_status', ChoiceType::class, [
                'label' => 'pinpoint_sale.admin.pinpoint_sale_common.form.repeat_status',
                'choices' => [
                    'pinpoint_sale.pinpoint_repeat_status_off' => PinpointRepeat::REPEAT_OFF,
                    'pinpoint_sale.pinpoint_repeat_status_on' => PinpointRepeat::REPEAT_ON
                ],
                'expanded' => true,
                'multiple' => false,
                'mapped' => false,
                'choice_attr' => function ($choiceValue, $key, $value) {
                    return [
                        'class' => 'pinpoint_repeat_type_radio',
                    ];
                },
            ])
            ->add('PinpointRepeat', PinpointRepeatType::class);

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, [
                $this->formEventListener, 'postSetData'
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, [
                $this->formEventListener, 'preSubmit'
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->formEventListener, 'postSubmit'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\PinpointSale\Entity\Pinpoint',
            'pinpoint_sale_common' => false,
        ]);
    }
}
