<?php
/*
* Plugin Name : uc_slide_sp
*/

namespace Plugin\uc_slide_sp\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class uc_slide_spDataType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * uc_slide_spDataType constructor.
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
        ->add('img_url', TextType::class, [
            'label' => '画像URL',
        ])
        ->add('img_alt', TextType::class, [
            'label' => '画像のAlt（説明文）',
            'required' => false,
        ])
        ->add('link_url', TextType::class, [
            'label' => 'リンクURL',
            'required' => false,
        ]);
    }
}
