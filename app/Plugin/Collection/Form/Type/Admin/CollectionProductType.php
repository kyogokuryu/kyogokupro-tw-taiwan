<?php

namespace Plugin\Collection\Form\Type\Admin;

use Eccube\Repository\ProductRepository;
use Plugin\Collection\Entity\CollectionProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CollectionProductType extends AbstractType
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Session
     */
    protected $session;

    /**
     * CollectionProductType constructor.
     * 
     * @param ProductRepository $productRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        Session $session
    ) {
        $this->productRepository = $productRepository;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product_id', HiddenType::class, [
                'mapped' => false,
                'error_bubbling' => false,
            ])
            ->add('sort_no', HiddenType::class)
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                // if data is null, this item has been deleted on ui.
                // To remove, set CollectionProduct::INVALID_SORT_NO to sort_no temporarily.
                $data = $event->getData();
                if ($data === null) {
                    $data = [];
                    $data['sort_no'] = CollectionProduct::INVALID_SORT_NO;
                    $event->setData($data);
                }
            })
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $productId = $form->get('product_id')->getData();

                if ($productId !== null) {
                    $Product = $this->productRepository->find($productId);
                    if ($Product === null) {
                        // MSG003: not existing product
                        $form->get('product_id')->addError(new FormError(trans('collection.admin.collection.csv.validation.not_existing_product')));
                    }
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();

                $form = $event->getForm();
                $productId = $form->get('product_id')->getData();

                if ($productId !== null) {
                    // when created new row
                    $Product = $this->productRepository->find($productId);
                    $data->setProduct($Product);

                    $Collection = $form->getParent()->getParent()->getData();
                    $data->setCollection($Collection);
                }

                $event->setData($data);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CollectionProduct::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'plugin_collection_product';
    }
}
