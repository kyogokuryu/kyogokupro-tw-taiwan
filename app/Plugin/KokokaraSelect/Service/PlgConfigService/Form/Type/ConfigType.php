<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/03/30
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $Configs = $options['data'];

        $builder
            ->add('plgConfigs', CollectionType::class, [
                'entry_type' => ConfigRowType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'data' => $Configs
            ]);
    }

}
