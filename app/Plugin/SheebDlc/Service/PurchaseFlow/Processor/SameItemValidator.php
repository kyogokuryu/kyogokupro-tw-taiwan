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
use Eccube\Entity\Customer;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Plugin\SheebDlc\PluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * 購入済みで且つ、ダウンロード可能な商品を購入できないようにする
 *
 * @CartFlow
 * @ShoppingFlow
 * @OrderFlow
 */
class SameItemValidator extends ItemValidator
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
     * @var Customer
     */
    private $loginCustomer;

    /**
     * @var int[]
     */
    private $downloadable_product_class_ids = [];

    /**
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->sheeb_dlcSaleType = PluginManager::getDlcSaleType($this->em);

        // ログイン中のCustomerを取得
        if (!$container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        if (null === $token = $container->get('security.token_storage')->getToken()) {
            $this->loginCustomer = null;
        } else if (!\is_object($token->getUser())) {
            $this->loginCustomer = null;
        } else {
            $this->loginCustomer = $token->getUser();
        }

        /**
         * ログインユーザーの注文履歴の中で 現在もダウンロード可能な ProductClassIdを保存
         * @var $orderRepository OrderRepository
         * @var $Order Order
         */
        if ($this->loginCustomer instanceof Customer) {
            $orderRepository = $this->em->getRepository(Order::class);
            $qb = $orderRepository->getQueryBuilderByCustomer($this->loginCustomer);
            $Orders = $qb->getQuery()->getResult();

            foreach ($Orders as $Order) {
                foreach ($Order->getOrderItems() as $OrderItem) {
                    if ($OrderItem->isDownloadable($this->sheeb_dlcSaleType, PluginManager::getConfig($this->em), true)) {
                        $this->downloadable_product_class_ids[] = $OrderItem->getProductClass()->getId();
                    }
                }
            }   
        }
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

        // ゲスト購入を無効にしているので、最終的には必ずログインするため、ログインしていなければ無視
        if (empty($this->loginCustomer)) {
            return;
        }

        // SaleType がダウンロードコンテンツじゃなきゃ無視
        $thisSaleType = $item->getProductClass()->getSaleType();
        if ($this->sheeb_dlcSaleType->getId() !== $thisSaleType->getId()) {
            return;
        }

        // この OrderItem が現在もダウンロード可能ならエラー
        if (in_array($item->getProductClass()->getId(), $this->downloadable_product_class_ids)) {
            $this->throwInvalidItemException('sheeb.dlc.purchase.purchased.error');
        }
    }

    protected function handle(ItemInterface $item, PurchaseContext $context)
    {
        $item->setQuantity(0);
    }
}
