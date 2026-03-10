<?php

namespace Plugin\ECCUBE4LineIntegration\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints as Assert;

class LineMessageCarouselType extends AbstractType
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
            // リンクURL
            ->add('link_url', UrlType::class, array(
                'label' => 'リンクURL',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => 1000)),
                ),
            ))
            // 画像データ
            ->add('image_file', FileType::class, array(
                'label' => '画像',
                'mapped' => false,
                'required' => false,
            ))
            // 画像ファイル名
            ->add('image_name', HiddenType::class, array(
                'required' => false,
            ))
            // ラベル (optional)
            ->add('label', TextType::class, array(
                'label' => 'リンクURL',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => 12)),
                ),
            ))

        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'line_message_carousel';
    }
}
