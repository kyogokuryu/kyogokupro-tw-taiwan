<?php
/*
* Plugin Name : [code]
*/

namespace Plugin\[code]\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class [code]ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
    * @var ContainerInterface
    */
    protected $containerInterface;

    /**
     * [code]ConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig, ContainerInterface $container)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->containerInterface = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    $builder
        ->add('title', TextType::class, [
            'label' => 'title',
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('display_title', ChoiceType::class, [
            'label' => 'タイトルの表示',
            'choices' => array_flip([
                '0' => '表示する',
                '1' => '表示しない'
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
