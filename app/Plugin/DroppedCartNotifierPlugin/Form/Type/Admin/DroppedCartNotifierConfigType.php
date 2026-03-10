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

namespace Plugin\DroppedCartNotifierPlugin\Form\Type\Admin;

use Eccube\Form\Type\ToggleSwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Plugin\DroppedCartNotifierPlugin\Entity\DroppedCartNotifierConfig;

class DroppedCartNotifierConfigType extends AbstractType
{
    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('past_day_to_notify', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0, 'max' => 100]),
                ],
                'attr' => ['min' => 0, 'max' => 100],
            ])
            ->add('max_cart_item', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1, 'max' => 10]),
                ],
                'attr' => ['min' => 1, 'max' => 10],
            ])
            ->add('max_recommended_item', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0, 'max' => 10]),
                ],
                'attr' => ['min' => 0, 'max' => 10],
            ])
            ->add('mail_subject', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 80]),
                ],
            ])
            ->add('base_url', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Url(),
                ],
            ])
            ->add('is_send_report_mail', ToggleSwitchType::class, [
                'constraints' => [
                    // none
                ],
            ]);
    }

    /**
     * Config.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DroppedCartNotifierConfig::class,
        ]);
    }
}
