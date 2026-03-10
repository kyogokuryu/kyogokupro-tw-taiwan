<?php

namespace Plugin\ECCUBE4LineIntegration\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints as Assert;

class LineMessageType extends AbstractType
{

    private $eccubeConfig;

    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->eccubeConfig;
        $builder
            // メッセージ
            ->add('message', TextareaType::class, array(
                'label' => 'テキスト',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => 2000)),
                ),
            ))
            // 画像ファイル名
            ->add('image_file', FileType::class, array(
                'label' => '画像',
                'mapped' => false,
                'required' => false,
            ))
            // 画像データ
            ->add('image', HiddenType::class, array(
                'required' => false,
            ))
            // 画像保存先ディレクトリのURL
            ->add('image_dir_url', HiddenType::class, array(
                'mapped' => false,
                'required' => false,
            ))
            // スタンプ
            ->add('stamp_package_id', IntegerType::class, array(
                'label' => 'PackageID',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_int_len'])),
                ),
            ))
            ->add('stamp_sticker_id', IntegerType::class, array(
                'label' => 'StickerID',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_int_len'])),
                ),
            ))
            // 画像カルーセル
            ->add('carousel_columns', CollectionType::class, array(
                'entry_type' => LineMessageCarouselType::class,
                'prototype' => true,
                'mapped' => false,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'line_message';
    }
}
