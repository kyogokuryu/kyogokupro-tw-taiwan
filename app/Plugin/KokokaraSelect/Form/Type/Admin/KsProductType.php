<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/11
 */

namespace Plugin\KokokaraSelect\Form\Type\Admin;


use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\ToggleSwitchType;
use Plugin\KokokaraSelect\Entity\KsProduct;
use Plugin\KokokaraSelect\Form\EventListener\KsProductTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class KsProductType extends AbstractType
{

    use KsProductTypeTrait;

    /** @var EccubeConfig */
    protected $config;

    public function __construct(
        EccubeConfig $config
    )
    {
        $this->config = $config;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'kokokara_select.admin.setting.ks_product.description',
                'required' => false,
            ])
            ->add('quantity', HiddenType::class, [
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->config['eccube_int_len'],
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
            ])
            ->add('priceView', ToggleSwitchType::class, [
                'label' => 'kokokara_select.admin.setting.ks_product.price_view',
            ])
            ->add('directSelect', ToggleSwitchType::class, [
                'label' => 'kokokara_select.admin.setting.ks_product.direct_select',
            ])
            ->add('KsSelectItemGroups', CollectionType::class, [
                'entry_type' => KsSelectItemGroupType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'error_bubbling' => false,
            ]);

        // EventListener
        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, [
                $this->ksProductTypeEventListener, "onKsProductTypePreSubmit"
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->ksProductTypeEventListener, "onKsProductTypePostSubmit"
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KsProduct::class,
        ]);
    }
}
