<?php

namespace Plugin\Ranking\Form\Type\Admin;

use Plugin\Ranking\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ->add('name', TextType::class, [
            //     'constraints' => [
            //         new NotBlank(),
            //         new Length(['max' => 255]),
            //     ],
            // ])
            ->add('target_period', ChoiceType::class, [
                'choices'  => [
                    '前日' => '1',
                    '過去1週間' => '2',
                    '過去2週間' => '3',
                    '過去3週間' => '4',
                    '過去1ヶ月' => '5',
                ],
            ])
            ->add('slider_auto_play', ChoiceType::class, [
                'choices'  => [
                    '自動' => '1',
                    '手動' => '0',
                ],
                'expanded' => true,
                'label' => 'スライダー再生',
            ])
            ->add('slider_design', ChoiceType::class, [
                'choices'  => [
                    'タイプ1' => '1',
                    'タイプ2' => '2',
                    'タイプ3' => '3',
                    'タイプ4' => '4',
                    'タイプ5' => '5',
                ],
            ])
            ->add('frame1_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠1 設定タイプ',
            ])
            ->add('frame1_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame2_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠2 設定タイプ',
            ])
            ->add('frame2_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame3_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠3 設定タイプ',
            ])
            ->add('frame3_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame4_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠4 設定タイプ',
            ])
            ->add('frame4_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame5_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠5 設定タイプ',
            ])
            ->add('frame5_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame6_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠6 設定タイプ',
            ])
            ->add('frame6_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame7_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠7 設定タイプ',
            ])
            ->add('frame7_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame8_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠8 設定タイプ',
            ])
            ->add('frame8_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame9_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠9 設定タイプ',
            ])
            ->add('frame9_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
            ->add('frame10_type', ChoiceType::class, [
                'choices'  => [
                    '自動' => '0',
                    '手動' => '1',
                ],
                'expanded' => true,
                'label' => '枠10 設定タイプ',
            ])
            ->add('frame10_value', IntegerType::class, [
                'required' => false,
                'constraints' => [
                ],
            ])
        ;
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
