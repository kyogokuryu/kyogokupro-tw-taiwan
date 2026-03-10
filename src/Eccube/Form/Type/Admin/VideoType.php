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
use Eccube\Entity\Category;
use Eccube\Entity\News;
use Eccube\Entity\Video;
use Eccube\Entity\VideoCategory;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\VideoCategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VideoType extends AbstractType
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
            ->add('name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_mtext_len']]),
                ],
            ])
            ->add('link', TextareaType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^((?:https?:)?\/\/)?((?:www)\.)?((?:youtube\.com\/watch+\?v=?)|(?:youtu\.be\/?))([\w\-]+)(\S+)?$/',
                        'message' => 'YouTubeのリンクである必要があります。',
                    ])
                ],
            ])
            ->add('second', NumberType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'max' => 180,
                        'min' => 1
                    ])
                ],
            ])
            ->add('point', NumberType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'max' => 10000,
                        'min' => 0
                    ])
                ],
            ])
            ->add('video_category_id', ChoiceType::class, [
                'required' => false,
                'choice_label' => 'name',
                'multiple' => false,
                'mapped' => false,
                'expanded' => false,
                'choices' => $this->videoCategoryRepository->findAll(),
                'choice_value' => 'id',
                'data' => $options['data']['videoCategory'] ?? null,
            ])
            ->add('status', ChoiceType::class, [
                'label' => false,
                'choices' => ['admin.content.news.display_status__show' => true, 'admin.content.news.display_status__hide' => false],
                'required' => true,
                'expanded' => false,
            ])
            ->add('product', ChoiceType::class, [
                'required' => false,
                'choice_label' => 'name',
                'multiple' => true,
                'mapped' => false,
                'expanded' => false,
                'choices' => $this->productRepository->findAll(),
                'choice_value' => 'id',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
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
