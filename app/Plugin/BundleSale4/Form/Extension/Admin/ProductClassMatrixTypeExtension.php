<?php
/**
 * This file is part of BundleSale4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\BundleSale4\Form\Extension\Admin;

use Symfony\Component\Form\AbstractTypeExtension;
use Eccube\Form\Type\Admin\ProductClassMatrixType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Plugin\BundleSale4\Repository\BundleItemRepository;
use Eccube\Repository\ProductClassRepository;

/**
 * セット商品に登録されている商品規格を非表示にしようとしたときエラーを発生させる。
 *
 * @author Akira Kurozumi <info@a-zumi.net>
 */
class ProductClassMatrixTypeExtension extends AbstractTypeExtension
{

    /**
     * @var BundleItemRepository
     */
    private $bundleItemRepository;

    /**
     * @var ProductClassRepository
     */
    private $productClassRepository;

    public function __construct(
        BundleItemRepository $bundleItemRepository,
        ProductClassRepository $productClassRepository
    )
    {
        $this->bundleItemRepository = $bundleItemRepository;
        $this->productClassRepository = $productClassRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['product_classes_exist']) {
            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $ProductClasses = $form['product_classes']->getData();

                foreach ($ProductClasses as $ProductClass) {
                    if (!$ProductClass->isVisible()) {
                        $BundleItem = $this->bundleItemRepository->findOneBy([
                            "ProductClass" => $ProductClass
                        ]);

                        if ($BundleItem) {
                            $productName = $ProductClass->formattedProductName();
                            $form['product_classes']->addError(new FormError(trans('plugin.bundle_sale.admin.product_class.matrix.update_error', ["%product_class%" => $productName])));
                            break;
                        }
                    }
                }
            });
        }
    }

    public function getExtendedType()
    {
        return ProductClassMatrixType::class;
    }

    public static function getExtendedTypes()
    {
        return [ProductClassMatrixType::class];
    }
}
