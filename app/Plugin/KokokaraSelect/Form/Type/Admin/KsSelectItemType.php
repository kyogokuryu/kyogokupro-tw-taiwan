<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/11
 */

namespace Plugin\KokokaraSelect\Form\Type\Admin;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\ProductClass;
use Eccube\Form\DataTransformer\EntityToIdTransformer;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Form\EventListener\KsProductTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class KsSelectItemType extends AbstractType
{

    use KsProductTypeTrait;

    protected $doctrine;

    protected $eccubeConfig;

    public function __construct(
        ManagerRegistry $doctrine,
        EccubeConfig $eccubeConfig
    )
    {
        $this->doctrine = $doctrine;
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder
                    ->create('ProductClass', HiddenType::class, [
                        'data_class' => null,
                        'attr' => [
                            'class' => 'kokokara_select_item_id',
                        ]
                    ])
                    ->addModelTransformer(new EntityToIdTransformer($this->doctrine->getManager(), ProductClass::class))
            )
            ->add('stock', NumberType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_int_len'],
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
            ])
            ->add('stockUnlimited', CheckboxType::class, [
                'label' => 'kokokara_select.admin.setting.ks_select_item.list.stock_unlimited',
                'value' => '1',
                'required' => false,
            ])
            ->add('sortNo', HiddenType::class, [
                'attr' => [
                    'class' => 'ks_select_item_sort_no'
                ]
            ]);

        $builder
            // 投入数量
            ->add('quantity', IntegerType::class, [
                'label' => 'kokokara_select.admin.setting.ks_select_item.quantity',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_int_len'],
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
                'attr' => [
                    'class' => 'ks_select_item_quantity',
                ]
            ]);

        // EventListener
        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->ksProductTypeEventListener, "onKsSelectItemTypePostSubmit"
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KsSelectItem::class,
        ]);
    }
}
