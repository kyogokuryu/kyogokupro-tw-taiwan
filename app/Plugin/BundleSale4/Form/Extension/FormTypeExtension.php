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

namespace Plugin\BundleSale4\Form\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\ProductRepository;
use Plugin\BundleSale4\Entity\BundleItem;
use Plugin\BundleSale4\Repository\BundleItemRepository;
use Plugin\BundleSale4\Request\Context;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * 商品規格が初期化されたときに商品規格が含まれるセット商品を非公開にする
 *
 * Class FormTypeExtension
 * @package Plugin\BundleSale4\Form\Extension
 */
class FormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ProductStatusRepository
     */
    private $productStatusRepository;

    /**
     * @var BundleItemRepository
     */
    private $bundleItemRepository;

    /**
     * @var Context
     */
    private $requestContext;

    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        ProductStatusRepository $productStatusRepository,
        BundleItemRepository $bundleItemRepository,
        Context $requestContext
    )
    {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->productStatusRepository = $productStatusRepository;
        $this->bundleItemRepository = $bundleItemRepository;
        $this->requestContext = $requestContext;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->requestContext->isRoute("admin_product_product_class_clear")) {
            $request= $this->requestContext->getMasterRequest();
            $Product = $this->productRepository->find($request->get('id'));

            $Products = [];
            if($Product instanceof Product) {
                $ProductClasses = $Product->getProductClasses()->toArray();
                $BundleItems = $this->bundleItemRepository->findByProductClass($ProductClasses);

                foreach($BundleItems as $BundleItem) {
                    $Products[$BundleItem->getProduct()->getId()] = $BundleItem;
                }
            }

            $builder
                ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($Products) {
                    $ProductStatus = $this->productStatusRepository->find(ProductStatus::DISPLAY_HIDE);

                    foreach($Products as $BundleItem) {
                        if($BundleItem instanceof BundleItem) {
                            $Product = $BundleItem->getProduct();
                            $Product->setStatus($ProductStatus);
                            $this->entityManager->persist($Product);
                        }
                    }
                    $this->entityManager->flush();
                });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }

    public static function getExtendedTypes()
    {
        return [FormType::class];
    }
}
