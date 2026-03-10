<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrimeMemberType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['label_on'] = isset($options['label_on']) ? $options['label_on'] : "";
        $view->vars['label_off'] = isset($options['label_off']) ? $options['label_off'] : "";
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => true,
            'choices' => array(
                "一般会員"=>0,
                "プライム会員"=>1,
                "ファミリー轻成员"=>2,            
            )
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
