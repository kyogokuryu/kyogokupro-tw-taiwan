<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/10
 */

namespace Plugin\KokokaraSelect\Form\Type\Admin;


use Eccube\Form\Type\ToggleSwitchType;
use Plugin\KokokaraSelect\Entity\KsProductOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KsProductOptionType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('selectOnly', ToggleSwitchType::class, [
                'label' => 'kokokara_select.admin.product.select_only',
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KsProductOption::class,
        ]);
    }
}
