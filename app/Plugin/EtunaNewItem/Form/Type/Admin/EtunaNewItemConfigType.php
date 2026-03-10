<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) Takashi Otaki All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EtunaNewItem\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\EtunaNewItem\Entity\EtunaNewItemConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * Class EtunaNewItemConfigType.
 */
class EtunaNewItemConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * EtunaNewItemConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('newitem_title', TextType::class, [
                'required' => false,
            ])
            ->add('newitem_sort', ChoiceType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => array(
                    '登録順' => '0',
                    '更新順' => '1'
                ),
                'multiple'  => false,
                'expanded' => true,
            ])
            ->add('newitem_count', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('newitem_disp_title', ChoiceType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => array(
                    '表示' => '1',
                    '非表示' => '0'
                ),
                'multiple'  => false,
                'expanded' => true,
            ])
            ->add('newitem_disp_price', ChoiceType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => array(
                    '表示' => '1',
                    '非表示' => '0'
                ),
                'multiple'  => false,
                'expanded' => true,
            ])
            ->add('newitem_disp_description_detail', ChoiceType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => array(
                    '表示' => '1',
                    '非表示' => '0'
                ),
                'multiple'  => false,
                'expanded' => true,
            ])
            ->add('newitem_disp_code', ChoiceType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => array(
                    '表示' => '1',
                    '非表示' => '0'
                ),
                'multiple'  => false,
                'expanded' => true,
            ])
            ->add('newitem_disp_cat', ChoiceType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => array(
                    '表示' => '1',
                    '非表示' => '0'
                ),
                'multiple'  => false,
                'expanded' => true,
            ])
        ;
    }

    /**
     * Config.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EtunaNewItemConfig::class,
        ]);
    }
}
