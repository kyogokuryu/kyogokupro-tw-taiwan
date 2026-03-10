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

use Customize\Repository\BrandRepository;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class BrandType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var BrandRepository
     */
    protected $brandRepository;

    /**
     * CategoryType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig, BrandRepository $brandRepository)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->brandRepository = $brandRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('image', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('image', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,  
            ])
            ->add('brand_iamge', FileType::class, [
                'multiple' => false,
                'required' => false,
                'mapped' => false,
            ])
            // 画像
            ->add('images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('add_images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('delete_images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ]);

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $Brand = $event->getData();

            // $Brands = $this->brandRepository->findBy(['name' => $Brand['name']]);
            // if (count($Brands) > 0) {
            //     $event->getForm()->addError(new FormError('これはすでに存在しているブランドです。'));
            // }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_product_brand';
    }
}
