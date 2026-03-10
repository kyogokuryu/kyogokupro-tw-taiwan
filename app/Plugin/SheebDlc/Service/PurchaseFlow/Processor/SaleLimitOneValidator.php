<?php

/*
 * Project Name: ダウンロードコンテンツ販売 プラグイン for 4.0
 * Copyright(c) 2019 Kenji Nakanishi. All Rights Reserved.
 *
 * https://www.facebook.com/web.kenji.nakanishi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\SheebDlc\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Annotation\CartFlow;
use Eccube\Annotation\OrderFlow;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\CartItem;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Master\SaleType;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Plugin\SheebDlc\PluginManager;

/**
 * 商品分類「ダウンロードコンテンツ」商品を
 * １個のみしか購入できないようにする
 *
 * @CartFlow
 * @ShoppingFlow
 * @OrderFlow
 */
class SaleLimitOneValidator extends ItemValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SaleType
     */
    private $sheeb_dlcSaleType;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->sheeb_dlcSaleType = PluginManager::getDlcSaleType($this->em);
    }
    
    /**
     * @param ItemInterface $item
     * @param PurchaseContext $context
     *
     * @throws InvalidItemException
     */
    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isProduct()) {
            return;
        }

        $thisSaleType = $item->getProductClass()->getSaleType();
        
        if (!empty($this->sheeb_dlcSaleType) && $this->sheeb_dlcSaleType->getId() === $thisSaleType->getId()) {
            $quantity = $item->getQuantity();
            if (1 < $quantity) {
                $this->throwInvalidItemException('sheeb.dlc.purchase.quantity.error');
            }
        }
    }

    protected function handle(ItemInterface $item, PurchaseContext $context)
    {
        $item->setQuantity(1);
    }
}
