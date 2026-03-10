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

use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Tests\EccubeTestCase;
use Plugin\BundleSale4\Service\PurchaseFlow\Processor\BundleItemStockMultipleValidator;
use Plugin\BundleSale4\Entity\BundleItem;

class StockMultipleValidatorTest extends EccubeTestCase
{
    /**
     * @var BundleItemStockMultipleValidator
     */
    protected $validator;

    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var OrderItem
     */
    protected $OrderItem1;

    /**
     * @var OrderItem
     */
    protected $OrderItem2;

    /**
     * @var Product
     */
    protected $Product;

    /**
     * @var ProductClass
     */
    protected $ProductClass;

    /**
     * @var ProductClass
     */
    protected $ProductClass2;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $container = self::$kernel->getContainer();
        $this->validator = $container->get(BundleItemStockMultipleValidator::class);

        $Product = $this->createProduct('テスト商品', 3);
        $Product2 = $this->createProduct('セット商品', 0);

        foreach($Product->getProductClasses() as $ProductClass) {
            $BundleItem = new BundleItem();
            $BundleItem->setProduct($Product);
            $BundleItem->setProductClass($ProductClass);
            $BundleItem->setQuantity(1);
            $Product2->addBundleItem($BundleItem);
        }
        $this->entityManager->persist($Product2);
        $this->entityManager->flush();

        $this->ProductClass = $Product->getProductClasses()->first();
        $this->ProductClass2 = $Product2->getProductClasses()->first();

        $this->Order = $this->createOrderWithProductClasses($this->createCustomer(),
            [$this->ProductClass2, $this->ProductClass2]);
        $this->OrderItem1 = $this->Order->getProductOrderItems()[0];
        $this->OrderItem2 = $this->Order->getProductOrderItems()[1];
    }

    public function testInstance()
    {
        self::assertInstanceOf(BundleItemStockMultipleValidator::class, $this->validator);
        self::assertSame($this->ProductClass2->getProduct()->getBundleItems(), $this->OrderItem1->getProductClass()->getProduct()->getBundleItems());
        self::assertSame($this->ProductClass2->getProduct()->getBundleItems(), $this->OrderItem2->getProductClass()->getProduct()->getBundleItems());
    }

    public function testValidStock()
    {
        $this->ProductClass2->setStockUnlimited(false);
        $this->ProductClass2->setStock(2);
        $this->OrderItem1->setQuantity(1);
        $this->OrderItem2->setQuantity(1);
        try {
            $this->validator->validate($this->Order, new PurchaseContext());
            self::assertTrue(true);
        } catch (InvalidItemException $e) {
            self::fail();
        }
    }

    public function testStockUnlimited()
    {
        $this->ProductClass2->setStockUnlimited(true);
        $this->ProductClass2->setStock(null);

        foreach($this->ProductClass2->getProduct()->getBundleItems() as $bundleItem) {
            $bundleItem->getProductClass()->setStockUnlimited(true);
            $bundleItem->getProductClass()->getStock(null);
        }

        $this->OrderItem1->setQuantity(1000);
        $this->OrderItem2->setQuantity(50);

        try {
            $this->validator->validate($this->Order, new PurchaseContext());
            self::assertTrue(true);
        } catch (InvalidItemException $e) {
            self::fail();
        }
    }

    public function testStockZero()
    {
        $this->ProductClass2->setStockUnlimited(false);
        $this->ProductClass2->setStock(0);
        $this->OrderItem1->setQuantity(1000);
        $this->OrderItem2->setQuantity(50);

        $this->expectException(InvalidItemException::class);
        $this->validator->validate($this->Order, new PurchaseContext());
    }

    public function testStockOver()
    {
        self::markTestSkipped();

        $this->ProductClass2->setStockUnlimited(false);
        $this->ProductClass2->setStock(100);
        $this->OrderItem1->setQuantity(50);
        $this->OrderItem2->setQuantity(51);

        $this->expectException(InvalidItemException::class);
        $this->validator->validate($this->Order, new PurchaseContext());
    }
}
