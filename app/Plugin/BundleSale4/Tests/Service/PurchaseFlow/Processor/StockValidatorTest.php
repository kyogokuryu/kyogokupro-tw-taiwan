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

use Eccube\Entity\CartItem;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Tests\EccubeTestCase;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\BundleSale4\Service\PurchaseFlow\Processor\BundleItemStockValidator;
use Plugin\BundleSale4\Entity\BundleItem;

class StockValidatorTest extends EccubeTestCase
{
    /**
     * @var BundleItemStockValidator
     */
    protected $validator;

    /**
     * @var CartItem
     */
    protected $cartItem;

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
        $this->validator = $container->get(BundleItemStockValidator::class);

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

        $this->cartItem = new CartItem();
        $this->cartItem->setProductClass($this->ProductClass2);
    }

    public function testInstance()
    {
        self::assertInstanceOf(BundleItemStockValidator::class, $this->validator);
        self::assertSame($this->ProductClass2->getProduct()->getBundleItems(), $this->cartItem->getProductClass()->getProduct()->getBundleItems());
    }

    public function testValidStock()
    {
        $this->cartItem->setQuantity(1);
        $this->validator->execute($this->cartItem, new PurchaseContext());
        self::assertEquals(1, $this->cartItem->getQuantity());
    }

    public function testValidStockFail()
    {
        $this->cartItem->setQuantity(PHP_INT_MAX);
        $result = $this->validator->execute($this->cartItem, new PurchaseContext());

        self::assertTrue($result->isWarning());
    }

    public function testValidStockOrder()
    {
        self::markTestSkipped();

        $Order = $this->createOrderWithProductClasses($this->createCustomer(),
            [$this->ProductClass2]);

        self::assertEquals($Order->getOrderItems()->first()->getProductClass(), $this->ProductClass2);

        $Order->getOrderItems()->first()->setQuantity(100);

        foreach ($this->ProductClass2->getProduct()->getBundleItems() as $bundleItem) {
            $bundleItem->getProductClass()->setStockUnlimited(false);
            $bundleItem->getProductClass()->getStock(10);
            $this->entityManager->persist($bundleItem);
        }
        $this->entityManager->flush();

        $this->validator->execute($Order->getOrderItems()->first(), new PurchaseContext());
        self::assertEquals(0, $Order->getOrderItems()->first()->getQuantity());
    }
}
