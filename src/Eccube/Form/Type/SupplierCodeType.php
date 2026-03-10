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

namespace Eccube\Form\Type;

use Eccube\Form\Type\Master\ProductListMaxType;
use Eccube\Form\Type\Master\ProductListOrderByType;
use Eccube\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierCodeType extends AbstractType
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $Categories = $this->categoryRepository
            ->getList(null, true);

        $builder->add('mode', HiddenType::class, [
            'data' => 'search',
        ]);
        $builder->add('category_id', EntityType::class, [
            'class' => 'Eccube\Entity\Category',
            'choice_label' => 'NameWithLevel',
            'choices' => $Categories,
            'placeholder' => 'common.select__all_products',
            'required' => false,
        ]);

        $builder->add('sub_category_id', EntityType::class, [
            'class' => 'Eccube\Entity\Category',
            'choice_label' => 'NameWithLevel',
            'choices' => $Categories,
            'placeholder' => 'common.select__all_products',
            'required' => false,
        ]);

        $builder->add('name', SearchType::class, [
            'required' => false,
            'attr' => [
                'maxlength' => 50,
            ],
        ]);
        $builder
            ->add('supplier_code', TextType::class, [
                'required' => false,
            ]);
        $builder
            ->add('favorite_user_id', TextType::class, [
                'required' => false,
            ]);

        $builder->add('pageno', HiddenType::class, []);
        $builder->add('disp_number', ProductListMaxType::class, [
            'label' => false,
        ]);
        $builder->add('orderby', ProductListOrderByType::class, [
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'supplier';
    }
}
