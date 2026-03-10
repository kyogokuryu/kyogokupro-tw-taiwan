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
use Eccube\Entity\Csv;
use Eccube\Entity\Delivery;
use Eccube\Entity\DeliveryFee;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Payment;
use Eccube\Entity\PaymentOption;
use Eccube\Entity\Product;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\CsvRepository;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Repository\PageRepository;
use Plugin\SheebDlc\Entity\Config;
use Plugin\SheebDlc\Repository\ConfigRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class PluginManager extends AbstractPluginManager
{
    // 初期値: ダウンロード期限までの日数
    const DEFAULT_DOWNLOAD_DUE_DAYS = 0;
    // 初期値: 再取得可能日数
    const DEFAULT_VIEWING_DAYS = 0;
    // 初期値: ダウンロード可能回数
    const DEFAULT_DOWNLOADABLE_COUNT = 0;
    
    const SALE_TYPE_NAME = 'ダウンロードコンテンツ';
    const TEMPLATE_DIR = __DIR__ . '/Resource/template/Mail/';
    const TEMPLATES = [
        'order.twig', 'order.html.twig',
        'shipping_notify.twig', 'shipping_notify.html.twig',
    ];
    const SEPARATOR = ' ,';

    const ACCEPT_MIME_FOR_BACKEND = [
        // 画像
        'image' => [
            'gif', 'jpeg', 'jpg', 'png', 'vnd.microsoft.icon'
        ],
        // 音声
        'audio' => [
            'mp3', 'mpeg', 'mpg'
        ],
        // 動画
        'video' => [
            'mp4', 'mpeg', 'mpg'
        ],
        // その他
        'application' => [
            // ドキュメント
            'pdf',
            // 圧縮ファイル
            'zip',
            'x-zip-compressed',
            // いろいろ
            'octet-stream'
        ]
    ];
    
    // ico のように、たまに mimeと異なることがあるので別でセット
    const ACCEPT_EXTENSION_FOR_BACKEND = [
        // 画像
        'gif', 'jpeg', 'jpg', 'png', 'ico',
        // 音声
        'mp3',
        // 動画
        'mp4', 'mpeg', 'mpg', 
        // ドキュメント
        'pdf',
        // 圧縮ファイル
        'zip',
    ];
    
    const ACCEPT_MIME_FOR_FRONTEND = '/(\.|\/)(gif|jpe?g|png|ico|mp3|mpg|mpeg|mp4|pdf|zip)$/i';
    
    const DOWNLOAD_PAGE_NAME = 'ダウンロードページ';
    
    const ENCRYPT_KEY = 'Hi50ddcLKerUm61@rR7;BpyFb0##a%TGc11Kybp.z4nh4C;2VvaP%4c';

    const GOOGLE_CREDENTIAL_PATH = __DIR__ . '/Credential/google_service_account.json';

    static private $DLC_SALE_TYPE = null;
    static private $DLC_CONFIG = null;
    /**
     * 商品種別「ダウンロードコンテンツ」を追加
     * テンプレートファイルを追加
     * 
     * @param array $meta
     * @param ContainerInterface $container
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        /**
         * @var $em EntityManagerInterface
         * @var $eccubeConfig EccubeConfig
         */
        $em = $container->get('doctrine.orm.entity_manager');
        $theme_dir = $container->getParameter('eccube_theme_front_dir');

        // 販売種別系(一度追加したらもう消さない)
        if (empty(self::getDlcSaleType($em))) {
            $this->createDeliveryType($em, $this->createSaleType($em));
        }
        
        // コンフィグデータ(uninstall時にテーブルごと消える)
        (function (EntityManagerInterface $em) {
            /**
             * @var $configRepository ConfigRepository
             */
            $configRepository = $em->getRepository(Config::class);
            if (empty($configRepository->get())) {
                $this->createConfig($em);
                $em->flush();
            }
        })($em);
        
        // 初期データ系(disable時に消す)
        (function (EntityManagerInterface $em, $theme_dir) {
            /**
             * @var $pageRepository PageRepository
             */
            $pageRepository = $em->getRepository(Page::class);
            $downloadPage = $pageRepository->findOneBy(['url' => 'sheeb_dlc_downloads']);

            if (empty($downloadPage)) {
                // CSVレコード
                $this->createCsvRecords($em);
                
                // Downloadページ
                $Page = $this->createDownloadPage($em);
                $em->flush(); // PageIdを確定させる
                $this->createPageLayout($em, $Page);
                $em->flush();

                // 初期ファイル配置
                $this->moveMailTemplates($theme_dir);
            }
        })($em, $theme_dir);
    }

    /**
     * テンプレートファイルを削除
     * 
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        /**
         * @var $em EntityManagerInterface
         */
        $em = $container->get('doctrine.orm.entity_manager');
        $theme_dir = $container->getParameter('eccube_theme_front_dir');
        $this->removeMailTemplates($theme_dir);
        $this->removePage($em);
        $this->removeCsvRecords($em);
        
        $em->flush();
    }

    /* *******************************
     *          File配置系
     * *******************************/

    public static function getPluginRootDir()
    {
        return __DIR__;
    }
    
    public function moveMailTemplates($theme_dir)
    {
        $blockDir = $theme_dir . '/Mail/';
        $fileSystem = new Filesystem();
        $backup_prefix = (new \DateTime())->format('Ymd') . '_DLC_backup_';

        foreach (self::TEMPLATES as $TEMPLATE) {
            // バックアップ
            if (is_file($blockDir . $TEMPLATE)) {
                $fileSystem->rename(
                    $blockDir . $TEMPLATE,
                    $blockDir . $backup_prefix . $TEMPLATE, true
                );
            }
            $fileSystem->copy(
                self::TEMPLATE_DIR . $TEMPLATE,
                $blockDir . $TEMPLATE, true
            );
        }
    }

    public function removeMailTemplates($theme_dir)
    {
        $blockDir = $theme_dir . '/Mail/';
        $fileSystem = new Filesystem();
        $backup_prefix = (new \DateTime())->format('Ymd') . '_DLC_backup_';

        // 削除ではなく、リネーム
        foreach (self::TEMPLATES as $TEMPLATE) {
            if ($fileSystem->exists($blockDir . $TEMPLATE)) {
                $fileSystem->rename(
                    $blockDir . $TEMPLATE,
                    $blockDir . $backup_prefix . $TEMPLATE, true
                );
            }
        }
    }

    /* *******************************
     *          初期データ系
     * *******************************/
    /**
     * @param EntityManagerInterface $em
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function createCsvRecords(EntityManagerInterface $em)
    {
        /**
         * @var $CsvType CsvType
         */
        $CsvType = $em->getRepository(CsvType::class)->find(CsvType::CSV_TYPE_PRODUCT);
        $this->createCsv($em, $CsvType, 'sheeb_download_content', '保存されているファイル名称(ファイルID)');
        $this->createCsv($em, $CsvType, 'sheeb_dlc_mime', 'ファイル形式(MIME)情報 例: image/ping');
        $this->createCsv($em, $CsvType, 'sheeb_dlc_save_url', 'コンテンツ保存先のURL');
        $this->createCsv($em, $CsvType, 'sheeb_dlc_download_due_days', '初回ダウンロード期限(日)');
        $this->createCsv($em, $CsvType, 'sheeb_dlc_viewing_days', '再ダウンロード期限(日)');
        $this->createCsv($em, $CsvType, 'sheeb_dlc_downloadable_count', 'ダウンロード可能回数(回)');
        $this->createCsv($em, $CsvType, 'sheeb_dlc_origin_file_name', 'ダウンロードファイル名');
    }

    private function removeCsvRecords(EntityManagerInterface $em)
    {
        $CsvList = $em->getRepository(Csv::class)->findBy([
            'field_name' => [
                'sheeb_download_content', 'sheeb_dlc_mime', 'sheeb_dlc_save_url',
                'sheeb_dlc_download_due_days', 'sheeb_dlc_viewing_days', 'sheeb_dlc_downloadable_count',
                'sheeb_dlc_origin_file_name'
            ]
        ]);

        foreach ($CsvList as $Csv) {
            $em->remove($Csv);
        }
    }

    
    /**
     * @param EntityManagerInterface $em
     * @param CsvType $CsvType
     * @param $field_name
     * @param $disp_name
     * @return Csv
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function createCsv(EntityManagerInterface $em, CsvType $CsvType, $field_name, $disp_name)
    {
        /**
         * @var CsvRepository $csvRepository
         */
        $csvRepository = $em->getRepository(Csv::class);
        $sortNo = $csvRepository->createQueryBuilder('c')
            ->select('COALESCE(MAX(c.sort_no), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        $Csv = new Csv();
        $Csv->setCsvType($CsvType)
            ->setEntityName(Product::class)
            ->setFieldName($field_name)
            ->setDispName($disp_name)
            ->setSortNo($sortNo);
        $em->persist($Csv);

        return $Csv;
    }
    
    /**
     * @param $SalaTypeRepository_OR_EntityManager
     * @return null|SaleType
     * @throws \Exception
     */
    static function getDlcSaleType($SalaTypeRepository_OR_EntityManager)
    {
        if (self::$DLC_SALE_TYPE instanceof SaleType) {
            return self::$DLC_SALE_TYPE;
        }
        
        $saleTypeRepository = null;
        if ($SalaTypeRepository_OR_EntityManager instanceof SaleTypeRepository) {
            $saleTypeRepository = $SalaTypeRepository_OR_EntityManager;
        } else if ($SalaTypeRepository_OR_EntityManager instanceof EntityManagerInterface) {
            $saleTypeRepository = $SalaTypeRepository_OR_EntityManager->getRepository(SaleType::class);
        } else {
            throw new \Exception();
        }

        self::$DLC_SALE_TYPE = $saleTypeRepository->findOneBy(['name' => self::SALE_TYPE_NAME]);
        return self::$DLC_SALE_TYPE;
    }

    /**
     * @param $ConfigRepository_OR_EntityManager
     * @return Config
     * @throws \Exception
     */
    static function getConfig($ConfigRepository_OR_EntityManager): Config
    {
        if (self::$DLC_CONFIG instanceof Config) {
            return self::$DLC_CONFIG;
        }
        
        $repository = null;
        if ($ConfigRepository_OR_EntityManager instanceof ConfigRepository) {
            $repository = $ConfigRepository_OR_EntityManager;
        } else if ($ConfigRepository_OR_EntityManager instanceof EntityManagerInterface) {
            $repository = $ConfigRepository_OR_EntityManager->getRepository(Config::class);
        } else {
            throw new \Exception();
        }

        self::$DLC_CONFIG = $repository->get();
        return self::$DLC_CONFIG;
    }

    /**
     * @param EntityManagerInterface $em
     * @return SaleType|bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function createSaleType(EntityManagerInterface $em)
    {
        // 採番
        $next_id = $em->createQueryBuilder()
                ->select('COALESCE(MAX(e.id), 0)')
                ->from(SaleType::class, 'e')
                ->getQuery()
                ->getSingleScalarResult() + 1;
        $next_sort_number = $em->createQueryBuilder()
                ->select('COALESCE(MAX(e.sort_no), 0)')
                ->from(SaleType::class, 'e')
                ->getQuery()
                ->getSingleScalarResult() + 1;

        $SaleType = new SaleType();
        $SaleType->setId($next_id);
        $SaleType->setName(self::SALE_TYPE_NAME);
        $SaleType->setSortNo($next_sort_number);
        $em->persist($SaleType);
        
        return $SaleType;
    }

    private function createDeliveryType(EntityManagerInterface $em, SaleType $SaleType)
    {
        $next_sort_number = $em->createQueryBuilder()
                ->select('COALESCE(MAX(e.sort_no), 0)')
                ->from(Delivery::class, 'e')
                ->getQuery()
                ->getSingleScalarResult() + 1;
        
        // --- Delivery 本体 ---
        $Delivery = new Delivery();
        $Delivery
            ->setVisible(true)
            ->setName('配送不要')
            ->setServiceName('ダウンロードコンテンツ')
            ->setSaleType($SaleType)
            ->setSortNo($next_sort_number);
        $em->persist($Delivery);
        $em->flush();

        // --- Delivery Fee (全て無料) ---
        $Prefs = $em->getRepository(Pref::class)->findAll();
        foreach ($Prefs as $Pref) {
            $DeliveryFee = new DeliveryFee();
            $DeliveryFee
                ->setFee('0.0')
                ->setPref($Pref)
                ->setDelivery($Delivery);
            $Delivery->addDeliveryFee($DeliveryFee);
        }

        // ---PaymentOptions (全てセット) ---
        $Payments = $em->getRepository(Payment::class)->findAll();
        /** @var $Payment Payment */
        foreach ($Payments as $Payment) {
            $PaymentOption = new PaymentOption();
            $PaymentOption
                ->setPaymentId($Payment->getId())
                ->setPayment($Payment)
                ->setDeliveryId($Delivery->getId())
                ->setDelivery($Delivery);
            $Delivery->addPaymentOption($PaymentOption);
        }
        
        $em->persist($Delivery);
    }

    private function createConfig(EntityManagerInterface $em)
    {
        $order_status_ids = [OrderStatus::PAID, OrderStatus::DELIVERED];
        
        $Config = new Config();
        $Config
            ->setId(1)
            ->setAvailableOrderStatus(implode(self::SEPARATOR, $order_status_ids))
            ->setMode(Config::MODE_LOCAL)
        ;
        $em->persist($Config);
    }

    private function createDownloadPage(EntityManagerInterface $em)
    {
        // 採番
        $next_id = $em->createQueryBuilder()
                ->select('COALESCE(MAX(e.id), 0)')
                ->from(Page::class, 'e')
                ->getQuery()
                ->getSingleScalarResult() + 1;

        $Page = new Page();
        $Page
            ->setId($next_id)
            ->setName(self::DOWNLOAD_PAGE_NAME)
            ->setUrl('sheeb_dlc_downloads')
            ->setFileName('@SheebDlc/Mypage/downloads')
            ->setEditType(Page::EDIT_TYPE_DEFAULT)
            ->setMetaRobots('noindex');
        $em->persist($Page);

        return $Page;
    }

    private function createPageLayout(EntityManagerInterface $em, Page $Page)
    {
        /**
         * @var $LayoutRepository PageLayout
         */
        $layoutRepository = $em->getRepository(Layout::class);
        $Layout = $layoutRepository->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        
        // 採番
        $next_sort_number = $em->createQueryBuilder()
                ->select('COALESCE(MAX(e.sort_no), 0)')
                ->from(PageLayout::class, 'e')
                ->getQuery()
                ->getSingleScalarResult() + 1;

        $PageLayout = new PageLayout();
        $PageLayout->setLayoutId($Layout->getId());
        $PageLayout->setLayout($Layout);
        $PageLayout->setPageId($Page->getId());
        $PageLayout->setSortNo($next_sort_number);
        $PageLayout->setPage($Page);
       
        $em->persist($PageLayout);
        return $PageLayout;
    }

    private function removePage(EntityManagerInterface $em)
    {
        $pageRepository = $em->getRepository(Page::class);
        $pageLayoutRepository = $em->getRepository(PageLayout::class);

        $Page = $pageRepository->findOneBy(['url' => 'sheeb_dlc_downloads']);
        if (!empty($Page)) {
            $PageLayout = $pageLayoutRepository->findOneBy(['page_id' => $Page->getId()]);
            if (!empty($PageLayout)) {
                $em->remove($PageLayout);
            }
            $em->remove($Page);
        }
    }

    public static function dumpToLog($object, $mark = '')
    {
        $target = __DIR__ . '/plugin.log';
        if (!empty($mark)) {
            file_put_contents($target, $mark, FILE_APPEND);
        }
        
        $cloner = new VarCloner();
        $dumper = new CliDumper();

        $dumper->dump(
            $cloner->cloneVar($object),
            function ($line, $depth) use ($target) {
                // A negative depth means "end of dump"
                if ($depth >= 0) {
                    // Adds a two spaces indentation to the line
                    $output = str_repeat('  ', $depth).$line."\n";
                    file_put_contents($target, $output, FILE_APPEND);
                }
            }
        );
    }

}
