<?php

namespace Plugin\A8SalesTag4\Form\Type\Admin;

use Plugin\A8SalesTag4\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
        $builder->add('eid', TextType::class, [
            'constraints' => [
                new NotBlank(),
                new Length(['min' => 12, 'max' => 12]),
            ],
	    'trim' => true,
        ]);

        $builder->add('pids', TextType::class, [
            'constraints' => [
                new Length(['min' => 15, 'max' => 255]),
            ],
	    'trim' => true,
            'required' => false,
        ]);

	$builder->add('is_enabled_crossdomain', CheckboxType::class, [
		'label' => 'クロスドメイン設定',
		'required' => false,
	]);
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
