<?php
/**
 * This file is part of BundleSale4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\BundleSale4\Form\Type\Admin;

use Plugin\BundleSale4\Entity\BundleItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Eccube\Form\DataTransformer\EntityToIdTransformer;
use Eccube\Entity\ProductClass;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints as Assert;

class BundleItemType extends AbstractType
{

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        EntityManagerInterface $entityManager, EccubeConfig $eccubeConfig
    )
    {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create('ProductClass', HiddenType::class, [
                    'label' => 'セット商品',
                    'required' => true,
                    'constraints' => [
                        new Assert\NotBlank()
                    ]
                ])->addModelTransformer(new EntityToIdTransformer($this->entityManager, ProductClass::class))
            )
            ->add("quantity", NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => 10,
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BundleItem::class,
        ]);
    }

}
