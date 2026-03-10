<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/11
 */

namespace Plugin\KokokaraSelect\Form\Type\Admin;


use Eccube\Common\EccubeConfig;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Form\EventListener\KsProductTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class KsSelectItemGroupType extends AbstractType
{

    use KsProductTypeTrait;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    )
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('groupName', TextType::class, [
                'label' => 'kokokara_select.admin.setting.ks_select_item_group.group_name',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'kokokara_select.admin.setting.ks_select_item_group.description',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'kokokara_select.admin.setting.ks_select_item_group.quantity',
                'required' => true,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_int_len'],
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
//                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'class' => 'ks_select_item_group_quantity',
                ]
            ])
            ->add('KsSelectItems', CollectionType::class, [
                'entry_type' => KsSelectItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'error_bubbling' => false,
                'prototype_name' => '__item_key__',
            ])
            ->add('sortNo', HiddenType::class, [
                'attr' => [
                    'class' => 'ks_select_item_group_sort_no'
                ]
            ]);

        // EventListener
        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, [
                $this->ksProductTypeEventListener, "onKsSelectItemGroupTypePreSubmit"
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->ksProductTypeEventListener, "onKsSelectItemGroupTypePostSubmit"
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KsSelectItemGroup::class,
        ]);
    }
}
