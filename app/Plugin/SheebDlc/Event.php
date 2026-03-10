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

namespace Plugin\SheebDlc;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\ProductRepository;
use Plugin\SheebDlc\Entity\Config;
use Plugin\SheebDlc\Repository\ConfigRepository;
use Plugin\SheebDlc\Service\SaveFile\SaveFileModuleFactory;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;

class Event implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // 管理画面「商品詳細画面」読み込み・描画処理への介入
            '@admin/Product/product.twig' => ['onTemplateProductEdit', 10],
            // 管理画面「商品詳細画面」保存処理への介入
            EccubeEvents::ADMIN_PRODUCT_EDIT_COMPLETE => ['onProductEditComplete', 10],
            // 管理画面「商品詳細画面」削除処理への介入
            EccubeEvents::ADMIN_PRODUCT_DELETE_COMPLETE => ['onProductEditDelete', 10],
            
            // フロント画面 会員ログイン画面
            'Shopping/login.twig' => ['onTemplateShoppingLogin', 10],
            // フロント画面 注文手続画面
            'Shopping/index.twig' => ['onTemplateShoppingIndex', 10],
            // フロント画面 注文確認画面
            'Shopping/confirm.twig' => ['onTemplateShoppingConfirm', 10],
            
            
            // フロント画面 マイページ 注文履歴詳細
            'Mypage/history.twig' => ['onTemplateMypageHistory', 10],
            
            // フロント画面 マイページのその他全ページ(共通ヘッダを修正するだけ)
            'Mypage/index.twig' => ['onTemplateMypageNavi', 10],
            'Mypage/change.twig' => ['onTemplateMypageNavi', 10],
            'Mypage/change_complete.twig' => ['onTemplateMypageNavi', 10],
            'Mypage/delivery.twig' => ['onTemplateMypageNavi', 10],
            'Mypage/delivery_edit.twig' => ['onTemplateMypageNavi', 10],
            'Mypage/favorite.twig' => ['onTemplateMypageNavi', 10],
            'Mypage/withdraw.twig' => ['onTemplateMypageNavi', 10],
            'Mypage/withdraw_confirm.twig' => ['onTemplateMypageNavi', 10],
            'Mypage/withdraw_complete.twig' => ['onTemplateMypageNavi', 10],
        ];
    }

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var ProductRepository
     */
    private $product_repository;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Packages
     */
    private $assets;

    public function __construct(EccubeConfig $eccubeConfig, ProductRepository $product_repository, EntityManagerInterface $em, Packages $package)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->product_repository = $product_repository;
        $this->em = $em;
        $this->assets = $package;
    }

    /**
     * 管理画面 商品管理 詳細画面
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateProductEdit(TemplateEvent $templateEvent)
    {
        // フロントエンド系
        $templateEvent->addSnippet('@SheebDlc/Modules/content_view.twig');
        $templateEvent->addSnippet('@SheebDlc/Admin/Product/product.twig');
    }

    /**
     * 管理画面 商品管理 詳細画面 保存時
     * @param EventArgs $event
     * @throws \Exception
     */
    public function onProductEditComplete(EventArgs $event, $param, $test)
    {
        /**
         * @var $Product Product
         * @var $ProductClass ProductClass
         * @var $configRepository ConfigRepository
         */
        $Product = $event->getArgument('Product');
        $file_name = $Product->getSheebDownloadContent();
        
        // 販売種別「ダウンロードコンテンツ」以外では何もしない
        $ProductClass = $Product->getProductClasses()->current();
        if ($ProductClass->getSaleType()->getId() !== PluginManager::getDlcSaleType($this->em)->getId()) {
            return;
        }
        
        $configRepository = $this->em->getRepository(Config::class);
        $module = SaveFileModuleFactory::get($this->eccubeConfig, $configRepository->get(), $this->assets, $file_name);

        /*
         * モジュールごとに保存先に保存
         * (今回保存するファイルが既に保存先にある場合はsave_urlについては何もしない)
         */
        if (!$module->isExistSaveFile($Product)) {
            $module->throwIfNotExistTempFile();
            $Product->setSheebDlcSaveUrl($module->save($Product));
        }
        
        /*
         * 数値系はデフォルトは0
         */
        if (empty($Product->getSheebDlcDownloadDueDays())) {
            $Product->setSheebDlcDownloadDueDays(PluginManager::DEFAULT_DOWNLOAD_DUE_DAYS);
        }
        if (empty($Product->getSheebDlcViewingDays())) {
            $Product->setSheebDlcViewingDays(PluginManager::DEFAULT_VIEWING_DAYS);
        }
        if (empty($Product->getSheebDlcDownloadableCount())) {
            $Product->setSheebDlcDownloadableCount(PluginManager::DEFAULT_DOWNLOADABLE_COUNT);
        }
        
        $this->em->persist($Product);
        $this->em->flush();
        
        /*
         * 一時ファイルを削除
         */
        if (!empty($file_name)) {
            $fs = new Filesystem();
            $fs->remove($module->getTempFilePath());    
        }
    }

    /**
     * 管理画面 商品管理 削除時
     * @param EventArgs $event
     * @throws \Exception
     */
    public function onProductEditDelete(EventArgs $event)
    {
        /**
         * @var $Product Product
         * @var $configRepository ConfigRepository
         */
        $Product = $event->getArgument('Product');
        $file_name = $Product->getSheebDownloadContent();

        if (empty($file_name)) {
            return;
        }
        
        $configRepository = $this->em->getRepository(Config::class);
        $module = SaveFileModuleFactory::get($this->eccubeConfig, $configRepository->get(), $this->assets, $file_name);

        /*
         * モジュールごとに削除
         */
        $module->remove($Product);
    }

    /**
     * フロント画面 会員ログイン画面
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateShoppingLogin(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@SheebDlc/Shopping/login.twig');
    }

    /**
     * フロント画面 注文手続画面
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateShoppingIndex(TemplateEvent $templateEvent)
    {
        if (defined('EXIST_DOWNLOAD_CONTENT')) {
            $templateEvent->addSnippet('@SheebDlc/Shopping/index.twig');
        }
    }

    /**
     * フロント画面 注文確認画面
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateShoppingConfirm(TemplateEvent $templateEvent)
    {
        if (defined('EXIST_DOWNLOAD_CONTENT')) {
            $templateEvent->addSnippet('@SheebDlc/Shopping/confirm.twig');
        }
    }

    public function onTemplateMypageNavi(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@SheebDlc/Mypage/navi.twig');
    }

    public function onTemplateMypageHistory(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@SheebDlc/Mypage/history.twig');
    }

    
}
