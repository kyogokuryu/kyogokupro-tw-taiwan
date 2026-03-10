<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/10
 */

namespace Plugin\KokokaraSelect\Form\Extension;


use Eccube\Entity\Product;
use Eccube\Form\Type\Admin\ProductType;
use Plugin\KokokaraSelect\Entity\KsProductOption;
use Plugin\KokokaraSelect\Form\Type\Admin\KsProductOptionType;
use Plugin\KokokaraSelect\Service\KsProductService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductTypeExtension extends AbstractTypeExtension
{

    /** @var KsProductService */
    protected $ksProductService;

    public function __construct(
        KsProductService $ksProductService
    )
    {
        $this->ksProductService = $ksProductService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // 選択
        $builder
            ->add('ksProductOption', KsProductOptionType::class, [
                'eccube_form_options' => [
                    'auto_render' => true,
                    'form_theme' => '@KokokaraSelect/admin/Product/product_add_view.twig'
                ],
            ]);

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

                /** @var Product $product */
                $product = $event->getData();

                if ($product) {
                    // 選択商品の場合は設定不可
                    if ($this->ksProductService->isKsProduct($product)) {
                        $form = $event->getForm();
                        $form->remove('ksProductOption');
                    }
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {

                $product = $event->getData();

                $form = $event->getForm();

                if (!$form->has('ksProductOption')) {
                    return;
                }

                /** @var KsProductOption $ksProductOption */
                $ksProductOption = $form->get('ksProductOption')->getData();

                $ksProductOption->setProduct($product);
            });
    }

    public function getExtendedType()
    {
        return ProductType::class;
    }
}
