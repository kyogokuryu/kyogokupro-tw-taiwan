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

namespace Plugin\BundleSale4\Tests\Form\Type\Admin;

use Eccube\Tests\Form\Type\AbstractTypeTestCase;
use Plugin\BundleSale4\Form\Type\Admin\BundleItemType;

class BundleItemTypeTest extends AbstractTypeTestCase
{
    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    protected $form;

    /**
     * @var array デフォルト値（正常系）を設定
     */
    protected $formData = [
        'ProductClass' => 1,
        'quantity' => '100',
    ];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        $this->form = $this->formFactory
            ->createBuilder(BundleItemType::class, null, ['csrf_protection' => false])
            ->getForm();

        $Product = $this->createProduct();
        $ProductClass = $Product->getProductClasses()->first();
        $this->formData['ProductClass'] = $ProductClass->getId();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidQuantity_NotNumeric()
    {
        $this->formData['quantity'] = 'abcde';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidQuantity_HasMinus()
    {
        $this->formData['quantity'] = '-12345';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidQuantity_Zero()
    {
        $this->formData['quantity'] = 0;

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidQuantity_OverMaxLength()
    {
        $this->formData['quantity'] = '12345678910'; //Max 10

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInValidQuantity_Blank()
    {
        $this->formData['quantity'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

}
