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

namespace Plugin\SheebDlc\Twig\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Entity\Product;
use Plugin\SheebDlc\PluginManager;
use Plugin\SheebDlc\Service\SaveFile\Modules\GoogleDrive;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Methods extends AbstractExtension
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SaleType 
     */
    private $dcSaleType;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->dcSaleType = PluginManager::getDlcSaleType($this->em);
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('isExistDownloadContentByOrder', array($this, 'isExistDownloadContentByOrder')),
            new TwigFunction('isExistDownloadContentByShipping', array($this, 'isExistDownloadContentByShipping')),
            new TwigFunction('isExistDownloadContentByProduct', array($this, 'isExistDownloadContentByProduct')),
            new TwigFunction('isExistDownloadZeroContentByProduct', array($this, 'isExistDownloadZeroContentByProduct')),
            new TwigFunction('isExistDownloadContentByOrderItem', array($this, 'isExistDownloadContentByOrderItem')),
            new TwigFunction('getDlcSaleTypeId', array($this, 'getDlcSaleTypeId')),
            new TwigFunction('isExistGoogleDriveCredential', array($this, 'isExistGoogleDriveCredential')),
        ];
    }

    public function getDlcSaleTypeId()
    {
        return $this->dcSaleType->getId();
    }

    /**
     * @param Order $Order
     * @return bool
     */
    public function isExistDownloadContentByOrder(Order $Order)
    {
        $result = false;
        /**
         * @var $Shipping Shipping
         */
        foreach ($Order->getShippings() as $Shipping) {
            if ($this->isExistDownloadContentByShipping($Shipping)) {
                $result = true;
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * @param Shipping $Shipping
     * @return bool
     */
    public function isExistDownloadContentByShipping(Shipping $Shipping)
    {
        $result = false;
        /**
         * @var $OrderItem OrderItem
         */
        foreach ($Shipping->getOrderItems() as $OrderItem) {
            if (!$OrderItem->isProduct()) {
                continue;
            }
            
            if ($OrderItem->getProductClass()->getSaleType()->getId() === $this->dcSaleType->getId()) {
                $result = true;
                break;
            } 
        }
        return $result;
    }

    /**
     * @param OrderItem $OrderItem
     * @return bool
     */
    public function isExistDownloadContentByOrderItem(OrderItem $OrderItem)
    {
        $result = false;
        if ($OrderItem->getProductClass() && $OrderItem->getProductClass()->getSaleType()->getId() === $this->dcSaleType->getId()) {
            $result = true;
        } 
        return $result;
    }

    /**
     * @param Product $Product
     * @return bool
     */
    public function isExistDownloadContentByProduct(Product $Product)
    {
        $result = false;
        foreach($Product->getProductClasses() as $class){        
            if($class->getSaleType()->getId() === $this->dcSaleType->getId()){
                $result = true;
                break;
            }
        }
        return $result;
    }


    /**
     * @param Product $Product
     * @return bool
     */
    public function isExistDownloadZeroContentByProduct(Product $Product)
    {
        $result = false;
        foreach($Product->getProductClasses() as $class){        
            if($class->getSaleType()->getId() === $this->dcSaleType->getId()){
                if($Product->getPrice02IncTaxMin() == 0){
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }


    public function isExistGoogleDriveCredential()
    {
        return GoogleDrive::isExistGoogleDriveCredential();
    }
}
