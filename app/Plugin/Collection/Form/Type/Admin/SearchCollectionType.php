<?php

namespace Plugin\Collection\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SearchCollectionType extends AbstractType
{
    public function __construct()
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code_name', TextType::class, [
                'label' => 'collection.admin.collection.label.code_name',
                'required' => false,
                'constraints' => [
                    //
                ],
            ])
            ->add('visible', ChoiceType::class, [
                'label' => 'collection.admin.collection.label.visible',
                'placeholder' => false,
                'choices' => [
                    'collection.admin.collection.label.enable' => 1,
                    'collection.admin.collection.label.disable' => 0,
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('display_from', DateType::class, [
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_display_from',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('display_to', DateType::class, [
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_display_to',
                    'data-toggle' => 'datetimepicker',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'plugin_collection_search';
    }
}
