<?php
/*
* Plugin Name : [code]
*/

namespace Plugin\[code]\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class [code]DataType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * [code]DataType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $columnArray = array(
            '12' => '1',
            '6' => '2',
            '4' => '3',
            '3' => '4',
            '2' => '6',
            '1' => '12',
        );

        $builder
        ->add('img_url', TextType::class, [
            'label' => '画像URL',
        ])
        ->add('img_alt', TextType::class, [
            'label' => '画像のAlt',
            'required' => false,
        ])
        ->add('link_url', TextType::class, [
            'label' => 'リンクURL',
            'required' => false,
        ])
        ->add('img_description', TextAreaType::class, [
            'label' => '画像の説明文（HTML可）',
            'required' => false,
        ])
        ->add('column_xs', ChoiceType::class, [
            'label' => '列（モバイル）',
            'choices' => array_flip($columnArray),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
        ])
        ->add('column_lg', ChoiceType::class, [
            'label' => '列（PC）',
            'choices' => array_flip($columnArray),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
        ]);
    }
}
