<?php

namespace Customize\Form\Admin;

use Customize\Entity\FaqCategory;
use Customize\Repository\FaqCategoryRepository;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FaqType extends AbstractType
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
                'multiple' => false,
                'mapped' => false,
                'expanded' => false,
                'placeholder' => '請選擇',
                'choices' => $this->faqCategoryRepository->findAll(),
                'choice_value' => function (FaqCategory $FaqCategory = null) {
                    return $FaqCategory ? $FaqCategory->getId() : null;
                },
            ])
            // 質問
            ->add('question', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            // 回答
            ->add('answer', TextareaType::class, [
                'attr' => [
                    // textareaの列設定
                    'rows' => 10,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_url_len']]),
                ],
            ])
            // TOPページに表示するか
            ->add('display_top', ChoiceType::class, [
                'expanded' => true,
                'choices' => [
                    '非表示' => 0,
                    '表示' => 1,
                ],
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
        return 'admin_faq';
    }
}