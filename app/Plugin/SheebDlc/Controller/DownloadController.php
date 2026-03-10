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

namespace Plugin\SheebDlc\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Product;
use Eccube\Entity\ProductStock;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\OrderRepository;
use Plugin\SheebDlc\Entity\Config;
use Plugin\SheebDlc\Entity\FreeDwProduct;
use Plugin\SheebDlc\PluginManager;
use Plugin\SheebDlc\Service\SaveFile\AbstractSaveFile;
use Plugin\SheebDlc\Service\SaveFile\SaveFileModuleFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Knp\Component\Pager\Paginator;


class DownloadController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \Symfony\Component\Asset\Packages
     */
    private $assets;
    
    public function __construct(EntityManagerInterface $em, Packages $asset)
    {
        $this->em = $em;
        $this->assets = $asset;
    }

    /**
     * ダウンロード可能商品一覧ページ
     * @Route("/mypage/downloads", name="sheeb_dlc_downloads", methods={"GET"})
     * @Template("@SheebDlc/Mypage/downloads.twig")
     * 
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function index(Request $request)
    {
        $available_orders = [];
        
        $Customer = $this->getUser();
        if (empty($Customer)) {
            throw new BadRequestHttpException();
        }

        /**
         * 会員の購入履歴を取得
         * @var $orderRepository OrderRepository
         * @var $Order Order
         * @var $OrderItem OrderItem
         */
        $orderRepository = $this->em->getRepository(Order::class);
        $qb = $orderRepository->getQueryBuilderByCustomer($Customer);
        $Orders = $qb->getQuery()->getResult();
        
        $dlcSaleType = PluginManager::getDlcSaleType($this->em);
        
        // 現在ダウンロード可能な OrderItem を抽出
        foreach ($Orders as $Order) {
            foreach ($Order->getOrderItems() as $OrderItem) {
                if ($OrderItem->isDownloadable($dlcSaleType, PluginManager::getConfig($this->em))) {
                    $available_orders[] = $OrderItem;
                }
                unset($OrderItem);
            }
        }
        
        return [ 'OrderItems' => $available_orders ];
    }


    /**
     * @Route("/mypage/download/{order_item_id}", name="sheeb_dlc_download")
     * 
     * @param Request $request
     * @param $order_item_id
     * @return Response
     * @throws \Exception
     */
    public function download(Request $request, $order_item_id)
    {
        // --- Check: ログインしているかどうか ---
        $Customer = $this->getUser();
        if (empty($Customer)) {
            throw new BadRequestHttpException();
        }

        // --- Check: CSRF対策
        //$this->isTokenValid();

        /**
         * @var $orderItemRepository OrderItemRepository
         * @var $OrderItem OrderItem
         */
        // --- Check: 存在しているOrderItemかどうか ---
        $OrderItem = (function ($order_item_id): OrderItem {
            $orderItemRepository = $this->em->getRepository(OrderItem::class);
            $OrderItem = $orderItemRepository->find($order_item_id);
            if (empty($OrderItem)) {
                throw new NotFoundHttpException();
            }
            return $OrderItem;
        })($order_item_id);
        
        // --- Check: ダウンロード可能かどうか ---
        if (!$OrderItem->isDownloadable(PluginManager::getDlcSaleType($this->em), PluginManager::getConfig($this->em))) {
            $this->addError($OrderItem->getSheebDlcError(), 'SheebDlc');
            return $this->redirectToRoute('sheeb_dlc_downloads');
        }

        // ダウンロード記録
        $this->recordDownload($OrderItem);
        
        // 出力処理
        return $this->outputDownloadContent($OrderItem);
    }

    /**
     * @Route("/mypage/freedownload/{id}", name="sheeb_dlc_freedownload")
     * 
     * @param Request $request
     * @param Product $Product
     * @return Response
     * @throws \Exception
     */
    public function freedownload(Request $request, Product $Product)
    {
        // --- Check: ログインしているかどうか ---
        $Customer = $this->getUser();
        if (empty($Customer)) {
            throw new BadRequestHttpException();
        }

        // --- Check: CSRF対策
        //$this->isTokenValid();

        /**
         * @var $orderItemRepository OrderItemRepository
         * @var $OrderItem OrderItem
         */
        // --- Check: 存在しているOrderItemかどうか ---
        /*
        $OrderItem = (function ($order_item_id): OrderItem {
            $orderItemRepository = $this->em->getRepository(OrderItem::class);
            $OrderItem = $orderItemRepository->find($order_item_id);
            if (empty($OrderItem)) {
                throw new NotFoundHttpException();
            }
            return $OrderItem;
        })($order_item_id);
        
        // --- Check: ダウンロード可能かどうか ---
        if (!$OrderItem->isDownloadable(PluginManager::getDlcSaleType($this->em), PluginManager::getConfig($this->em))) {
            $this->addError($OrderItem->getSheebDlcError(), 'SheebDlc');
            return $this->redirectToRoute('sheeb_dlc_downloads');
        }

        // ダウンロード記録
        $this->recordDownload($OrderItem);
        */
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')->from('Plugin\SheebDlc\Entity\FreeDwProduct','a');
        $qb->where('a.Product = :product_id')->setParameter('product_id', $Product->getId());
        $qb->andWhere('a.Customer = :customer_id')->setParameter('customer_id', $Customer->getId());
        $data = $qb->getQuery()->getOneOrNullResult();
        //$pagination = $paginator->paginate($data, 1, 1);
        if($data){ //$pagination->count() > 0){
            //


        }else{
            // New
            $FreeDwProduct = new FreeDwProduct();
            $FreeDwProduct->setProduct($Product);
            $FreeDwProduct->setCustomer($Customer);
            $this->entityManager->persist($FreeDwProduct);
            $this->entityManager->flush($FreeDwProduct);

            //
            foreach ($Product->getProductClasses() as $ProductClass) {
                $stock = $ProductClass->getStock() - 1;                
                $ProductClass->setStock($stock);
                $ProductStock = $ProductClass->getProductStock();
                $ProductStock->setStock($stock);
            }
            $this->entityManager->flush();

        }


        // 出力処理
        //return $this->outputDownloadContent($OrderItem);


        $Config = PluginManager::getConfig($this->em);
        //$Product = Product::find($product_id);//$OrderItem->getProduct();

        $mime_group = (function (Product $Product) {
            $mime = $Product->getSheebDlcMime();
            $mime = explode('/', $mime);
            $group = reset($mime);
            if (!isset(PluginManager::ACCEPT_MIME_FOR_BACKEND[$group])) {
                throw new UnsupportedMediaTypeHttpException();
            }
            return $group;
        })($Product);
        
        $module = SaveFileModuleFactory::get($this->eccubeConfig, $Config, $this->assets);

        switch ($mime_group) {
            case 'image':
                $response = $module->output($Product);//$this->outputImage($Config, $OrderItem, $module);
                break;
            case 'audio':
                $response = $module->output($Product);//$this->outputAudio($Config, $OrderItem, $module);
                break;
            case 'video':
                $response = $module->output($Product);//$this->outputVideo($Config, $OrderItem, $module);
                break;
            case 'application':
                $response = $module->outputInBrowser($Product);//$this->outputApplication($Config, $OrderItem, $module);
                break;
            case 'pdf':
                $response = $module->outputInBrowser($Product);//$this->outputApplication($Config, $OrderItem, $module);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
                break;
        }
        
        return $response;

    }


    /**
     * ダウンロード回数などをDBへ記録
     * 
     * @param OrderItem $OrderItem
     */
    private function recordDownload(OrderItem $OrderItem)
    {
        // 初回ダウンロード記録
        if (empty($OrderItem->getSheebDlcFirstDownloadDatetime())) {
            $OrderItem->setSheebDlcFirstDownloadDatetime(new \DateTime());
        }
        
        // ダウンロード回数記録
        $before = $OrderItem->getSheebDlcDownloadCount() ?? 0;
        $OrderItem->setSheebDlcDownloadCount($before + 1);
        
        $this->em->persist($OrderItem);
        $this->em->flush();
    }

    /**
     * 出力メイン処理
     * 
     * @param OrderItem $OrderItem
     * @return Response
     * @throws \Exception
     */
    private function outputDownloadContent(OrderItem $OrderItem): Response
    {
        $Config = PluginManager::getConfig($this->em);
        $Product = $OrderItem->getProduct();

        $mime_group = (function (Product $Product) {
            $mime = $Product->getSheebDlcMime();
            $mime = explode('/', $mime);
            $group = reset($mime);
            if (!isset(PluginManager::ACCEPT_MIME_FOR_BACKEND[$group])) {
                throw new UnsupportedMediaTypeHttpException();
            }
            return $group;
        })($Product);
        
        $module = SaveFileModuleFactory::get($this->eccubeConfig, $Config, $this->assets);

        switch ($mime_group) {
            case 'image':
                $response = $this->outputImage($Config, $OrderItem, $module);
                break;
            case 'audio':
                $response = $this->outputAudio($Config, $OrderItem, $module);
                break;
            case 'video':
                $response = $this->outputVideo($Config, $OrderItem, $module);
                break;
            case 'application':
                if(preg_match('/pdf/',$Product->getSheebDlcMime())){
                    $response = $module->outputInBrowser($Product);//$this->outputApplication($Config, $OrderItem, $module);
                }else{
                    $response = $this->outputApplication($Config, $OrderItem, $module);
                }
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
                break;
        }
        
        return $response;
    }

    /**
     * 単純にダウンロード出力する
     * 
     * @param Config $Config
     * @param OrderItem $OrderItem
     * @param AbstractSaveFile $module
     * @return Response
     */
    private function outputCommon(Config $Config, OrderItem $OrderItem, AbstractSaveFile $module)
    {
        return $module->output($OrderItem->getProduct());
    }

    private function outputImage(Config $Config, OrderItem $OrderItem, AbstractSaveFile $module)
    {
        return $this->outputCommon($Config, $OrderItem, $module);
    }

    private function outputAudio(Config $Config, OrderItem $OrderItem, AbstractSaveFile $module)
    {
        return $this->outputCommon($Config, $OrderItem, $module);
    }

    private function outputVideo(Config $Config, OrderItem $OrderItem, AbstractSaveFile $module)
    {
        return $this->outputCommon($Config, $OrderItem, $module);
    }

    private function outputApplication(Config $Config, OrderItem $OrderItem, AbstractSaveFile $module)
    {
        return $this->outputCommon($Config, $OrderItem, $module);
    }
}
