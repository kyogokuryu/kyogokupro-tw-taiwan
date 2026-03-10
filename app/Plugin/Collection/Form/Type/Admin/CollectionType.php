<?php

namespace Plugin\Collection\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\Collection\Entity\Collection;
use Plugin\Collection\Repository\CollectionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType as SymfonyCollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class CollectionType extends AbstractType
{
    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @var int max size of uploaded csv file
     */
    private $csvMaxSize;

    /**
     * CollectionType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(CollectionRepository $collectionRepository,
                                EccubeConfig $eccubeConfig)
    {
        $this->collectionRepository = $collectionRepository;
        $this->csvMaxSize = $eccubeConfig['eccube_csv_size'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('collection_code', TextType::class, [
                'label' => false,
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9-_]*$/',
                        'message' => 'collection.form_error.invalid_characters'
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => false,
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ])
            ->add('description', TextType::class,[
                'required' => false,
            ])
            ->add('file_name', FileType::class, [
                'multiple' => true,
                'required' => false,
                'mapped' => false,
            ])
            ->add('images', SymfonyCollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('add_images', SymfonyCollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('delete_images', SymfonyCollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
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
            ])
            ->add('import_file', FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => $this->csvMaxSize.'M',
                    ]),
                ],
            ])
            ->add('CollectionProducts', SymfonyCollectionType::class, [
                'entry_type' => CollectionProductType::class,
                'data' => $builder->getData()->getCollectionProducts(),
                'allow_add' => true,
            ])->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
                $form = $event->getForm();
                $formCollection = $form->getData();
                $collectionCode = $formCollection->getCollectionCode();
                $SameCodeCollection = $this->collectionRepository->findOneBy([
                    'collection_code' => $collectionCode,
                    'deleted' => false
                ]);

                if ($SameCodeCollection) {
                    if ($formCollection->getId() != $SameCodeCollection->getId()) {
                        $message = trans('collection.form_error.unique_code');
                        $form['collection_code']->addError(new FormError($message));
                    }
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Collection::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // sort CollectionProducts by sort_no, DESC
        usort($view['CollectionProducts']->children, function (FormView $a, FormView $b) {
            /** @var CollectionProducts $objectA */
            $ObjectA = $a->vars['data'];
            /** @var CollectionProducts $objectB */
            $ObjectB = $b->vars['data'];

            $posA = $ObjectA->getSortNo();
            $posB = $ObjectB->getSortNo();

            if ($posA == $posB) {
                return 0;
            }

            return ($posA > $posB) ? -1 : 1;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'plugin_collection';
    }
}
