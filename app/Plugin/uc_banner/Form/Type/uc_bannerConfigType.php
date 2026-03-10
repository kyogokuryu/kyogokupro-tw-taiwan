<?php
/*
* Plugin Name : uc_banner
*/

namespace Plugin\uc_banner\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class uc_bannerConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * uc_bannerConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('title', TextType::class, [
            'label' => '見出し',
        ])
        ->add('display_title', ChoiceType::class, [
            'label' => '見出しを表示',
            'choices' => array_flip([
                '0' =>  trans('admin.common.choiceType.yes'),
                '1' =>  trans('admin.common.choiceType.no'),
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
        ])
        ->add('display_description', ChoiceType::class, [
            'label' => '説明文を表示',
            'choices' => array_flip([
                '0' =>  trans('admin.common.choiceType.yes'),
                '1' =>  trans('admin.common.choiceType.no'),
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
        ]);
    }
}
