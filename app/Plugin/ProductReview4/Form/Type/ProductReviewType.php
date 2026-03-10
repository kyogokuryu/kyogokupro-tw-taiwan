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

namespace Plugin\ProductReview4\Form\Type;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Master\SexType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Plugin\ProductReview4\Entity\ProductReview;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class ProductReviewType
 * [商品レビュー]-[レビューフロント]用Form.
 */
class ProductReviewType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ProductReviewType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->eccubeConfig;
        $builder
            ->add('reviewer_name', TextType::class, [
                'label' => 'product_review.form.product_review.reviewer_name',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $config['eccube_stext_len']]),
                ],
                'attr' => [
                    'maxlength' => $config['eccube_stext_len'],
                ],
            ])
            ->add('reviewer_url', TextType::class, [
                'label' => 'product_review.form.product_review.reviewer_url',
                'required' => false,
                'constraints' => [
                    new Assert\Url(),
                    new Assert\Length(['max' => $config['eccube_mltext_len']]),
                ],
                'attr' => [
                    'maxlength' => $config['eccube_mltext_len'],
                ],
            ])
            ->add('sex', SexType::class, [
                'required' => false,
            ])
            ->add('recommend_level', ChoiceType::class, [
                'label' => 'product_review.form.product_review.recommend_level',
                'choices' => array_flip([
                    '0' => '',
                    '1' => '★',
                    '2' => '★★',
                    '3' => '★★★',
                    '4' => '★★★★',
                    '5' => '★★★★★',
                ]),
                'expanded' => true,
                'multiple' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'product_review.form.product_review.title',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $config['eccube_stext_len']]),
                ],
                'attr' => [
                    'maxlength' => $config['eccube_stext_len'],
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'product_review.form.product_review.comment',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $config['eccube_ltext_len']]),
                ],
                'attr' => [
                    'maxlength' => $config['eccube_ltext_len'],
                ],
            ])
            ->add('pic1_image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'mimeTypes' => [
                            'image/*',
                        ]
                    ])
                ]
            ])
            ->add('pic2_image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'mimeTypes' => [
                            'image/*',
                        ]
                    ])
                ]
            ])
            ->add('pic3_image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'mimeTypes' => [
                            'image/*',
                        ]
                    ])
                ]
            ])
            ->add('pic4_image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'mimeTypes' => [
                            'image/*',
                        ]
                    ])
                ]
            ])
            ->add('pic1', HiddenType::class)
            ->add('pic2', HiddenType::class)
            ->add('pic3', HiddenType::class)
            ->add('pic4', HiddenType::class)
            ;


        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event){
                $form = $event->getForm();
                $ProductReview = $event->getData();
                
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event){
                $form = $event->getForm();
                $ProductReview = $event->getData();

                if($ProductReview instanceof ProductReview){
                
                    $destination = $this->eccubeConfig["eccube_save_image_dir"]."/review/";

                    foreach(["pic1","pic2","pic3","pic4"] as $pic_key){
                        $pic_name = $pic_key . "_image";
                        $uploadFile1 = $form->get($pic_name)->getData();
                        if($uploadFile1) {
                            // ファイルアップロード
                            $orgFileName1 = $uploadFile1->getClientOriginalName();
                            $info = pathinfo($orgFileName1);
                            $newFileName1 = sha1($info["filename"]) .".". $info["extension"];

                            $uploadFile1->move($destination, $newFileName1);
                            //$filename = $this->fileUploader->upload($file);
                            
                            $mvFile = "/html/upload/save_image/review/".$newFileName1;
                            call_user_func_array([$ProductReview, "set".$pic_key], [$mvFile] );
                            //$form->setData('pic1_hidden', $orgFileName1);
                        }else{

                            $orgFile = $form->get($pic_key)->getData();
                            if($orgFile){
                                call_user_func_array([$ProductReview, "set".$pic_key], [$orgFile] );
                            }
                        }
                    }

                }
            })
        ;

    }
}
