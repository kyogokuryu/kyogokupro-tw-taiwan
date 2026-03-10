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

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Product;
use Eccube\Form\Type\Admin\ProductType;
use Plugin\BundleSale4\Form\Type\Admin\BundleItemType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Plugin\BundleSale4\Repository\BundleItemRepository;
use Eccube\Entity\Master\ProductStatus;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Akira Kurozumi <info@a-zumi.net>
 */
class ProductTypeExtension extends AbstractTypeExtension
{

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var BundleItemRepository
     */
    private $bundleItemRepository;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(
        EccubeConfig $eccubeConfig,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        BundleItemRepository $bundleItemRepository
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->bundleItemRepository = $bundleItemRepository;
    }

    /**
     * BundleCollectionExtension.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('BundleItems', CollectionType::class, [
                'label' => 'plugin.bundle_product.block.title',
                'entry_type' => BundleItemType::class,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'mapped' => false
            ]);

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                $Product = $event->getData();

                if ($Product instanceof Product) {
                    $form['BundleItems']->setData($Product->getBundleItems());
                }
            });

        // セット商品を追加して商品が登録されていなかった場合、削除してエラーメッセージを表示する
        //向井セット商品関係無効
        // $builder
        //     ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event){
        //         $form = $event->getForm();
        //         $data = $event->getData();

        //         $BundleItems = [];
        //         $isEmpty = false;

        //         if(isset($data["BundleItems"])) {
        //             foreach($data["BundleItems"] as $BundleItem) {
        //                 if($BundleItem["ProductClass"]) {
        //                     $BundleItems[] = $BundleItem;
        //                 }else{
        //                     $isEmpty = true;
        //                 }
        //             }
        //             $data["BundleItems"] = $BundleItems;

        //             $event->setData($data);

        //             if($isEmpty) {
        //                 $form->addError(new FormError(""));
        //                 $this->session->getFlashBag()->add('eccube.admin.error', trans('plugin.bundle_sale.admin.product.update_error.product.empty'));
        //             }
        //         }
        //     });

        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $Product = $event->getData();

                if ($Product instanceof Product) {
                    if ($form->isValid()) {
                        $BundleItems = $this->bundleItemRepository->findBy([
                            "Product" => $Product
                        ]);

                        foreach ($BundleItems as $BundleItem) {
                            $this->entityManager->remove($BundleItem);
                        }

                        $BundleItems = $Product->getBundleItems();
                        foreach ($BundleItems as $BundleItem) {
                            $BundleItem->setProduct($Product);
                        }
                    }
                }

            });

        // 非公開にしたときにセット商品に登録されていた場合エラーを発生させる
        //向井セット商品関係無効
        // $builder
        //     ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
        //         $form = $event->getForm();
        //         $Product = $event->getData();

        //         if ($form->isValid()) {
        //             if ($Product instanceof Product) {
        //                 if (!is_null($Product->getId()) && $Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
        //                     $ProductClasses = $Product->getProductClasses()->toArray();

        //                     $BundleItems = $this->bundleItemRepository->countByProductClass($ProductClasses);

        //                     if ($BundleItems > 0) {
        //                         $form->addError(new FormError("error"));
        //                         $this->session->getFlashBag()->add('eccube.admin.error', trans('plugin.bundle_sale.admin.product.update_error', ["%product%" => $Product->getName(), "%product_status%" => $Product->getStatus()]));
        //                     }
        //                 }
        //             }
        //         }
        //     });
    }

    /**
     * product admin form name.
     *
     * @return string
     */
    public function getExtendedType()
    {
        return ProductType::class;
    }

    public static function getExtendedTypes()
    {
        return [ProductType::class];
    }

}
