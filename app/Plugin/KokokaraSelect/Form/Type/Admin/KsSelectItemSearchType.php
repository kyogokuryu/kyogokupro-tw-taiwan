<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/31
 */

namespace Plugin\KokokaraSelect\Form\Type\Admin;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class KsSelectItemSearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productClassId', HiddenType::class);
    }
}
