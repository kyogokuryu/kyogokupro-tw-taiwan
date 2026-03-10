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

namespace Plugin\BundleSale4\Tests\Service\PurchaseFlow\Processor;

use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Tests\EccubeTestCase;
use Plugin\BundleSale4\Service\PurchaseFlow\Processor\BundleItemStockReduceProcessor;
use Plugin\BundleSale4\Entity\BundleItem;

class StockReduceProcessorTest extends EccubeTestCase
{
    private $processor;

    public function setUp()
    {
        parent::setUp();

        $this->processor = new BundleItemStockReduceProcessor($this->entityManager);
    }

    public function testPrepare()
    {
        /** @var Product $Product */
        $Product = $this->createProduct('テスト商品', 3);
        /** @var Product $Product2 */
        $Product2 = $this->createProduct('セット商品', 0);
        /** @var ProductClass $ProductClass2 */
        $ProductClass2 = $Product2->getProductClasses()[0];

        // Product2にセット商品を追加
        /** @var ProductClass $ProductClass */
        foreach ($Product->getProductClasses() as $ProductClass) {
            $BundleItem = new BundleItem();
            $BundleItem->setProduct($Product);
            $BundleItem->setProductClass($ProductClass);
            $BundleItem->setQuantity(1);
            $Product2->addBundleItem($BundleItem);
        }
        $this->entityManager->persist($Product2);
        $this->entityManager->flush();

        // Productの在庫を10に設定
        /** @var ProductClass $ProductClass */
        $ProductClass = $Product->getProductClasses()[0];
        $ProductClass->setStockUnlimited(false);
        $ProductClass->setStock(10);
        $ProductClass->getProductStock()->setStock(10);
        $this->entityManager->persist($ProductClass);
        $this->entityManager->flush();

        // 数量3の受注
        $Customer = $this->createCustomer();
        $Order = $this->createOrderWithProductClasses($Customer, [$ProductClass2]);
        $OrderItem = $Order->getProductOrderItems()[0];
        $OrderItem->setQuantity(3);

        $this->processor->prepare($Order, new PurchaseContext());

        // 在庫が7に減っている
        $ProductClass = $this->entityManager->find(ProductClass::class, $ProductClass->getId());
        self::assertEquals(7, $ProductClass->getStock());
    }

    public function testRollback()
    {
        /** @var Product $Product */
        $Product = $this->createProduct('テスト商品', 0);
        /** @var Product $Product2 */
        $Product2 = $this->createProduct('セット商品', 0);
        /** @var ProductClass $ProductClass2 */
        $ProductClass2 = $Product2->getProductClasses()[0];

        // Product2にセット商品を追加
        /** @var ProductClass $ProductClass */
        foreach ($Product->getProductClasses() as $ProductClass) {
            $BundleItem = new BundleItem();
            $BundleItem->setProduct($Product);
            $BundleItem->setProductClass($ProductClass);
            $BundleItem->setQuantity(1);
            $Product2->addBundleItem($BundleItem);
        }
        $this->entityManager->persist($Product2);
        $this->entityManager->flush();

        // Productの在庫を7に設定
        $ProductClass = $Product->getProductClasses()[0];
        $ProductClass->setStockUnlimited(false);
        $ProductClass->setStock(7);
        $ProductClass->getProductStock()->setStock(7);
        $this->entityManager->persist($ProductClass);
        $this->entityManager->flush();

        // 数量3の受注
        $Customer = $this->createCustomer();
        $Order = $this->createOrderWithProductClasses($Customer, [$ProductClass2]);
        $OrderItem = $Order->getProductOrderItems()[0];
        $OrderItem->setQuantity(3);

        $this->processor->rollback($Order, new PurchaseContext());

        // 在庫が10に戻っている
        $ProductClass = $this->entityManager->find(ProductClass::class, $ProductClass->getId());
        self::assertEquals(10, $ProductClass->getStock());
    }
}
