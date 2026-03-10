<?php
/*
* Plugin Name : UICube
*/

namespace Plugin\UICube\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ConfigType extends AbstractType
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

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $arrPlugin = [
      "ヘッダー",
      "フッター",
      "ショップガイド",
      "新着商品",
      "売れ筋商品",
      "バナー",
      "スライドショー",
      "カテゴリメニュー",
      "新着情報",
    ];

    $builder
    ->add('name', TextType::class, [
        'label' => 'name',
        'attr' => array(
             'placeholder' => 'プラグイン名',
        ),
        'constraints' => [
            new Assert\NotBlank(),
        ],
    ])
    ->add('code', TextType::class, [
        'label' => 'code',
        'attr' => array(
             'placeholder' => 'PluginName',
        ),
        'constraints' => [
            new Assert\NotBlank(),
            new Assert\Regex(array(
                'pattern' => '/^[0-9a-zA-Z\/_]+$/',
            )),
        ],
    ])
    ->add('choised_plugin', ChoiceType::class, [
        'label' => 'choised_plugin',
        'required' => true,
        'expanded' => false,
        'multiple' => false,
        'empty_data' => false,
        'choices' => array_flip($arrPlugin),
    ]);
  }
}
