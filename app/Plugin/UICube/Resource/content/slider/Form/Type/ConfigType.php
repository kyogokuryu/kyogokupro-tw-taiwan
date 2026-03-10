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

class [code]ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * [code]ConfigType constructor.
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
        ->add('block_type', ChoiceType::class, [
            'label' => 'ブロックタイプ',
            'choices' => array_flip([
                '0' => trans('admin.common.choiceType.box'),
                '1' => trans('admin.common.choiceType.full_width'),
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('slide_type', ChoiceType::class, [
            'label' => 'スライドタイプ',
            'choices' => array_flip([
                '0' => trans('admin.common.choiceType.default'),
                '1' => trans('admin.common.choiceType.carousel'),
                '2' => trans('admin.common.choiceType.carousel'),
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
    }
}
