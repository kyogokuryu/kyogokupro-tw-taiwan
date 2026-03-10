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

namespace Eccube\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\PageCountdown;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\VideoCategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PageCountdownType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    protected $videoCategoryRepository;

    protected $productRepository;

    public function __construct(VideoCategoryRepository $videoCategoryRepository, EccubeConfig $eccubeConfig, ProductRepository $productRepository)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->videoCategoryRepository = $videoCategoryRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('times', IntegerType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'max' => 180,
                        'min' => 1
                    ])
                ],
            ])
            
            ->add('second', IntegerType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'max' => 10000,
                        'min' => 1
                    ])
                ],
            ])
            ->add('point', IntegerType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'max' => 10000,
                        'min' => 1
                    ])
                ],
            ])
            ->add('interval', IntegerType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'max' => 10000,
                        'min' => 0
                    ])
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PageCountdown::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return '';
    }
}
