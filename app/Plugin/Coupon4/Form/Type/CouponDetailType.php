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

namespace Plugin\Coupon4\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Form\DataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class CouponDetailType.
 */
class CouponDetailType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * CouponDetailType constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * buildForm.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create('Product', HiddenType::class)
                    ->addModelTransformer(new DataTransformer\EntityToIdTransformer($this->entityManager, '\Eccube\Entity\Product'))
            )
            ->add(
                $builder->create('Category', HiddenType::class)
                    ->addModelTransformer(new DataTransformer\EntityToIdTransformer($this->entityManager, '\Eccube\Entity\Category'))
            )
            ->add('id', HiddenType::class, [
                'label' => 'プロモーションコード詳細ID',
                'required' => false,
            ])
            ->add('detail_discount_rate', IntegerType::class, [
                'label' => 'plugin_coupon.admin.label.discount_rate',
                'required' => false,
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 100,
                    ]),
                ],
            ])
            ->add('delivery_free_flag', ChoiceType::class, [
                'choices' => array_flip([
                    0 => '送料あり',
                    1 => '送料なし',
                ]),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('coupon_type', HiddenType::class, [
                'required' => false,
            ]);
    }

    /**
     * configureOptions.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\Coupon4\Entity\CouponDetail',
        ]);
    }

    /**
     * getName.
     *
     *  @return string
     */
    public function getName()
    {
        return 'admin_plugin_coupon_detail';
    }
}
