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
use Eccube\Entity\Delivery;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\SaleType;
use Eccube\Repository\DeliveryRepository;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\SheebDlc\PluginManager;

/**
 * 商品種別「ダウンロードコンテンツ」と他の商品種別の同時購入をできなくする
 * 
 * @CartFlow
 * @ShoppingFlow
 * @OrderFlow
 */
class SaleTypeValidator extends ItemHolderValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SaleType
     */
    private $sheeb_dlcSaleType;
    
    /**
     * @var DeliveryRepository
     */
    protected $deliveryRepository;

    /**
     * @param EntityManagerInterface $em
     * @param DeliveryRepository $deliveryRepository
     */
    public function __construct(EntityManagerInterface $em, DeliveryRepository $deliveryRepository)
    {
        $this->em = $em;
        $this->sheeb_dlcSaleType = PluginManager::getDlcSaleType($this->em);
        
        $this->deliveryRepository = $deliveryRepository;
    }

    protected function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // バリデーションが有効かどうか
        $enable = false;
        // ダウンロードコンテンツ以外の商品種別アイテムが存在するかどうか
        $is_exist_other_sale_type = false;
        
        foreach ($itemHolder->getItems() as $item) {
            if (false === $item->isProduct()) {
                continue;
            }

            if ($this->sheeb_dlcSaleType->getId() === $item->getProductClass()->getSaleType()->getId()) {
                if (!defined('EXIST_DOWNLOAD_CONTENT')) {
                    define('EXIST_DOWNLOAD_CONTENT', true);
                }
                $enable = true;
            } else {
                $is_exist_other_sale_type = true;
            }

            // 商品種別「ダウンロードコンテンツ」と、その他の商品種別が混在しているのでエラー
            if ($enable && $is_exist_other_sale_type) {
                $this->throwInvalidItemException('sheeb.dlc.purchase.sale_type.error');
            }
        }
    }
}
