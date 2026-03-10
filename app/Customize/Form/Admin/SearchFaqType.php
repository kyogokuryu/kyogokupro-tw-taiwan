<?php

namespace Customize\Form\Admin;

use Customize\Entity\FaqCategory;
use Customize\Repository\FaqCategoryRepository;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFaqType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var FaqCategoryRepository
     */
    protected $faqCategoryRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        FaqCategoryRepository $faqCategoryRepository
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->faqCategoryRepository = $faqCategoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('FaqCategory', ChoiceType::class, [
                'choice_label' => 'Name',
                'placeholder' =>  trans('admin.filter.placeholder.category'),
                'required' => false,
                'choices' => $this->faqCategoryRepository->findAll(),
                'choice_value' => function (FaqCategory $FaqCategory = null) {
                    return $FaqCategory ? $FaqCategory->getId() : null;
                },
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_search_faq';
    }
}