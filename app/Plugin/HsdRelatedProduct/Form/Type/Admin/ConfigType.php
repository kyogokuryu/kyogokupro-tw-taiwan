<?php

namespace Plugin\HsdRelatedProduct\Form\Type\Admin;

use Plugin\HsdRelatedProduct\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('max_num', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                    new Regex(array('pattern' => '/^[0-9]+$/')),
                ),
            ))
            ->add('max_row_num', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                    new Regex(array('pattern' => '/^[0-9]+$/')),
                ),
            ))
            ->add('title', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
            ))
            ->add('show_price', ChoiceType::class, array(
                'choices' => array('価格を表示する' => 'show_price', '価格を表示しない' => 'not_show_price'),
                'constraints' => array(
                    new NotBlank(),
                ),
            ))
            ->add('show_type', ChoiceType::class, array(
                'choices' => array('スライダーなし' => 'normal', 'スライダーあり 3個表示' => 'per3', 'スライダーあり 4個表示' => 'per4', 'スライダーあり 5個表示' => 'per5', 'スライダー フリップ' => 'flip', 'スライダー 3Dキューブ' => 'cube', 'スライダー カバーフロー' => 'coverflow'),
                'constraints' => array(
                    new NotBlank(),
                ),
            ))
            ->add('pagination', ChoiceType::class, array(
                'choices' => array('あり' => 'true', 'なし' => 'false'),
                'constraints' => array(
                    new NotBlank(),
                ),
            ))
            ->add('navbuttons', ChoiceType::class, array(
                'choices' => array('あり' => 'true', 'なし' => 'false'),
                'constraints' => array(
                    new NotBlank(),
                ),
            ))
            ->add('showloop', ChoiceType::class, array(
                'choices' => array('あり' => 'true', 'なし' => 'false'),
                'constraints' => array(
                    new NotBlank(),
                ),
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
