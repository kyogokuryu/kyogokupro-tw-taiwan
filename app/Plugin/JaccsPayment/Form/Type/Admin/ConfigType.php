<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JaccsPayment\Form\Type\Admin;

use Plugin\JaccsPayment\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ConfigType
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('shop_code', TextType::class, [
                'label' => trans('jaccs_payment.admin.config.shop_code.name'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 12]),
                ],
            ])
            ->add('link_password', TextType::class, [
                'label' => trans('jaccs_payment.admin.config.link_password.name'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 8, 'max' => 15]),
                ],
            ])
            ->add('service', ChoiceType::class, [
                'label' => trans('jaccs_payment.admin.config.service.name'),
                'required' => true,
                'multiple' => false,
                'expanded' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => [
                    trans('jaccs_payment.admin.config.service.option_2') => 2,
                    trans('jaccs_payment.admin.config.service.option_3') => 3,
                ],
            ])
            ->add('is_error_mail', CheckboxType::class, [
                'label' => trans('jaccs_payment.admin.config.is_error_mail.name'),
                'required' => false,
            ])
            ->add('is_condition_mail', CheckboxType::class, [
                'label' => trans('jaccs_payment.admin.config.is_condition_mail.name'),
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => trans('jaccs_payment.admin.config.email.name'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(['strict' => true]),
                ],
                'required' => false,
            ])
            ->add('batch_type', ChoiceType::class, [
                'label' => trans('jaccs_payment.admin.config.batch_type.name'),
                'required' => true,
                'multiple' => false,
                'expanded' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => [
                    trans('jaccs_payment.admin.config.batch_type.option_0') => 0,
                    trans('jaccs_payment.admin.config.batch_type.option_1') => 1,
                ],
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
