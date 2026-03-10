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

use Customize\Entity\Brand;
use Customize\Form\Type\BrandType;
use Customize\Repository\BrandRepository;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Category;
use Eccube\Form\Type\Master\ProductStatusType;
use Eccube\Form\Validator\TwigLint;
use Eccube\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProductType.
 */
class ProductType extends AbstractType
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    protected $brandRepository;

    /**
     * ProductType constructor.
     *
     * @param CategoryRepository $categoryRepository
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        EccubeConfig $eccubeConfig,
        BrandRepository $brandRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->brandRepository = $brandRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // 商品規格情報
            ->add('class', ProductClassType::class, [
                'mapped' => false,
            ])
            // 基本情報
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            // 基本情報
            ->add('product_sub_name', TextType::class, [
                'constraints' => [
                //    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])            
            ->add('product_image', FileType::class, [
                'multiple' => true,
                'required' => false,
                'mapped' => false,
            ])
            ->add('description_image', FileType::class, [
                'multiple' => true,
                'required' => false,
                'mapped' => false,
            ])
            ->add('description_detail', TextareaType::class, [
                'constraints' => [
                //    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])
            ->add('description_list', TextareaType::class, [
                'required' => false,
                'constraints' => [
                //    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])
            ->add('Category', ChoiceType::class, [
                'choice_label' => 'Name',
                'multiple' => true,
                'mapped' => false,
                'expanded' => true,
                'choices' => $this->categoryRepository->getList(null, true),
                'choice_value' => function (Category $Category = null) {
                    return $Category ? $Category->getId() : null;
                },
            ])

            // 詳細な説明
            ->add('Brand', ChoiceType::class, [
                'choice_label' => 'name',
                'multiple' => false,
                'mapped' => false,
                'expanded' => true,
                'choices' => $this->brandRepository->getList(),
                'choice_value' => function (Brand $brand = null) {
                    return $brand ? $brand->getId() : null;
                },
            ])
            ->add('Tag', EntityType::class, [
                'class' => 'Eccube\Entity\Tag',
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('t')
                    ->orderBy('t.sort_no', 'DESC');
                },
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
            ])
            ->add('search_word', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])
            // サブ情報
            ->add('free_area', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new TwigLint(),
                ],
            ])

            // 右ブロック
            ->add('Status', ProductStatusType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            // ->add('brand', BrandType::class)
            ->add('note', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])

            // タグ
            ->add('tags', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
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
            ])
            // 画像
            ->add('description_images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('description_add_images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('description_delete_images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('return_link', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('llmo_target_audience', TextareaType::class, [
                'required' => false,
            ])
            ->add('llmo_problem_solution', TextareaType::class, [
                'required' => false,
            ])
            ->add('llmo_usage_scene', TextareaType::class, [
                'required' => false,
            ])
            ->add('llmo_supervised_comment', TextareaType::class, [
                'required' => false,
            ])
            ->add('llmo_how_to_use', TextareaType::class, [
                'required' => false,
            ])
            ->add('llmo_features', TextareaType::class, [
                'required' => false,
            ])
            ->add('llmo_faq', TextareaType::class, [
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_product';
    }
}
