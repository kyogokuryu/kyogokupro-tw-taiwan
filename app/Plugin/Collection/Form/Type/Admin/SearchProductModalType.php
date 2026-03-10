<?php

namespace Plugin\Collection\Form\Type\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Category;
use Eccube\Form\DataTransformer;
use Eccube\Form\Type\Master\CategoryType as MasterCategoryType;
use Eccube\Repository\CategoryRepository;
use Plugin\Collection\Entity\CollectionProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SearchProductModalType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * SearchProductModalType constructor.
     * 
     * @param EntityManagerInterface $entityManager
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository
    ) {
        $this->entityManager = $entityManager;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', TextType::class, [
                'label' => 'admin.product.product',
                'required' => false,
            ])
            ->add('category_id', MasterCategoryType::class, [
                'choice_label' => 'NameWithLevel',
                'label' => 'admin.product.category',
                'placeholder' => 'common.select__all_products',
                'required' => false,
                'multiple' => false,
                'expanded' => false,
                'choices' => $this->categoryRepository->getList(null, true),
                'choice_value' => function (Category $Category = null) {
                    return $Category ? $Category->getId() : null;
                },
            ])
            ->add(
                $builder
                    ->create('Product', HiddenType::class)
                    ->addModelTransformer(new DataTransformer\EntityToIdTransformer($this->entityManager, '\Eccube\Entity\Product'))
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // 'data_class' => CollectionProduct::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'plugin_collection_search_product_modal';
    }
}
