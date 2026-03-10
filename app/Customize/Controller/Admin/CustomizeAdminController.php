<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller\Admin;

use Carbon\Carbon;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Eccube\Controller\Admin\AdminController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\ProductStock;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\PluginApiException;
use Eccube\Form\Type\Admin\ChangePasswordType;
use Eccube\Form\Type\Admin\LoginType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\MemberRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\PluginApiService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Eccube\Application;
use Eccube\Kernel;
use Eccube\Util\CacheUtil;

use Customize\Repository\PointLogRepository;
use Customize\Repository\AccessLogRepository;

use Doctrine\ORM\EntityManagerInterface;


class CustomizeAdminController extends AdminController
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var AuthenticationUtils
     */
    protected $helper;

    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var PointLogRepository
     */
    protected $pointLogRepository;

    /**
     * @var AccessLogRepository
     */
    protected $accessLogRepository;

    /** @var PluginApiService */
    protected $pluginApiService;

    /**
     * @var array 売り上げ状況用受注状況
     */
    private $excludes = [OrderStatus::CANCEL, OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::RETURNED];

    /**
     * AdminController constructor.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AuthenticationUtils $helper
     * @param MemberRepository $memberRepository
     * @param EncoderFactoryInterface $encoderFactory
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param CustomerRepository $custmerRepository
     * @param ProductRepository $productRepository
     * @param PointLogRepository $pointLogRepository
     * @param AccessLogRepository $accessLogRepository
     * @param PluginApiService $pluginApiService
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AuthenticationUtils $helper,
        MemberRepository $memberRepository,
        EncoderFactoryInterface $encoderFactory,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        CustomerRepository $custmerRepository,
        ProductRepository $productRepository,
        PointLogRepository $pointLogRepository,
        AccessLogRepository $accessLogRepository,
        PluginApiService $pluginApiService
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->helper = $helper;
        $this->memberRepository = $memberRepository;
        $this->encoderFactory = $encoderFactory;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->customerRepository = $custmerRepository;
        $this->productRepository = $productRepository;
        $this->pointLogRepository = $pointLogRepository;
        $this->accessLogRepository = $accessLogRepository;
        $this->pluginApiService = $pluginApiService;
    }

    /**
     * 管理画面ホーム
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @Route("/%eccube_admin_route%/", name="admin_homepage")
     * @Template("@admin/index.twig")
     */
    public function index(Request $request)
    {
        $adminRoute = $this->eccubeConfig['eccube_admin_route'];
        $is_danger_admin_url = false;
        if ($adminRoute === 'admin') {
            $is_danger_admin_url = true;
        }
        /**
         * 受注状況.
         */
        $excludes = [];
        $excludes[] = OrderStatus::CANCEL;
        $excludes[] = OrderStatus::DELIVERED;
        $excludes[] = OrderStatus::PENDING;
        $excludes[] = OrderStatus::PROCESSING;
        $excludes[] = OrderStatus::RETURNED;

        $event = new EventArgs(
            [
                'excludes' => $excludes,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ADMIM_INDEX_ORDER, $event);
        $excludes = $event->getArgument('excludes');

        // 受注ステータスごとの受注件数.
        $Orders = $this->getOrderEachStatus($excludes);

        // 受注ステータスの一覧.
        $Criteria = new Criteria();
        $Criteria
            ->where($Criteria::expr()->notIn('id', $excludes))
            ->orderBy(['sort_no' => 'ASC']);
        $OrderStatuses = $this->orderStatusRepository->matching($Criteria);

        /**
         * 売り上げ状況
         */
        $event = new EventArgs(
            [
                'excludes' => $this->excludes,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ADMIM_INDEX_SALES, $event);
        $this->excludes = $event->getArgument('excludes');

        $elm = $this;
        $saleTypeToDay = $this->cache('CustomizeAdminController.SaleTypeSalesByDay', function() use($elm){ 
            return $elm->getSaleTypeSalesByDay(new \DateTime()); 
        }, 60*10);
        $saleTypeYesterday = $this->cache('CustomizeAdminController.SaleTypeSalesByYesterday', function() use($elm){
            return $elm->getSaleTypeSalesByDay(new \DateTime('-1 day'));
        }, 60*10);
        $saleTypeMonth = $this->cache('CustomizeAdminController.SaleTypeSalesByMonth', function() use($elm){
            return $elm->getSaleTypeSalesByMonth(new \DateTime());
        }, 60*10); 


        // 今日の売上/件数
        $salesToday = $this->getSalesByDay(new \DateTime());
        // 昨日の売上/件数
        $salesYesterday = $this->getSalesByDay(new \DateTime('-1 day'));
        // 今月の売上/件数
        $salesThisMonth = $this->getSalesByMonth(new \DateTime());
        // 今日の売上個数 20211119 kikuzawa
        $quantityToday = $this->getQuantityByDay(new \DateTime());
        // 昨日の売上個数 20211119 kikuzawa
        $quantityYesterday = $this->getQuantityByDay(new \DateTime('-1 day'));
        // 今月の売上個数 20211124 kikuzawa
        $quantityThisMonth = $this->getTotalQuantityForMonth(new \DateTime());

        /**
         * ショップ状況
         */
        // 在庫切れ商品数
        $countNonStockProducts = $this->countNonStockProducts();

        // 取り扱い商品数
        $countProducts = $this->countProducts();

        // 本会員数
        $countCustomers = $this->countCustomers();

        // ファミリー会員
        $countPrimes = $this->getPrimeMemberCount();
        $countPrimeLights = $this->getPrimeLightMemberCount();

        $event = new EventArgs(
            [
                'Orders' => $Orders,
                'OrderStatuses' => $OrderStatuses,
                'salesThisMonth' => $salesThisMonth,
                'salesToday' => $salesToday,
                'salesYesterday' => $salesYesterday,
                'countNonStockProducts' => $countNonStockProducts,
                'countProducts' => $countProducts,
                'countCustomers' => $countCustomers,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ADMIM_INDEX_COMPLETE, $event);

        // 推奨プラグイン
        $recommendedPlugins = [];
        try {
            $recommendedPlugins = $this->pluginApiService->getRecommended();
        } catch (PluginApiException $ignore) {
        }

        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subMonths(3)->startOfMonth();
        $rawYear = $elm->getData($fromDate, $toDate, 'Y/m', false, true);
        $rawYear = array_reverse($rawYear);

        $currentDateString = $toDate->format('Y/m');
        $oneMonthAgoDateString = $toDate->subMonth()->format('Y/m');
        $twoMonthsAgoDateString = $toDate->subMonth()->format('Y/m');
        $threeMonthsAgoDateString = $toDate->subMonth()->format('Y/m');

        $currentDateComparison = $this->calculateMonthOverMonth($rawYear, $currentDateString, $oneMonthAgoDateString);
        $oneMonthAgoComparison = $this->calculateMonthOverMonth($rawYear, $oneMonthAgoDateString, $twoMonthsAgoDateString);
        $twoMonthsAgoComparison = $this->calculateMonthOverMonth($rawYear, $twoMonthsAgoDateString, $threeMonthsAgoDateString);

        $allSales = [
            $currentDateString => ['price' => $rawYear[$currentDateString]['price'], 'comparison' => $currentDateComparison],
            $oneMonthAgoDateString => ['price' => $rawYear[$oneMonthAgoDateString]['price'], 'comparison' => $oneMonthAgoComparison],
            $twoMonthsAgoDateString => ['price' => $rawYear[$twoMonthsAgoDateString]['price'], 'comparison' => $twoMonthsAgoComparison]
        ];


        return [
            'Orders' => $Orders,
            'OrderStatuses' => $OrderStatuses,
            'salesThisMonth' => $salesThisMonth,
            'salesToday' => $salesToday,
            'salesYesterday' => $salesYesterday,
            'countNonStockProducts' => $countNonStockProducts,
            'countProducts' => $countProducts,
            'countCustomers' => $countCustomers,
            'recommendedPlugins' => $recommendedPlugins,
            'is_danger_admin_url' => $is_danger_admin_url,
            'quantityToday' => $quantityToday,//20211121 kikuzawa
            'quantityYesterday' => $quantityYesterday,//20211121 kikuzawa
            'quantityThisMonth' => $quantityThisMonth,//20211121 kikuzawa

            'saleTypeSalesToday' => $saleTypeToDay,
            'saleTypeSalesMonth' => $saleTypeMonth,
            'saleTypeSalesYesterday' => $saleTypeYesterday,

            'countPrimes' => $countPrimes,
            'countPrimeLights' => $countPrimeLights,

            'allSales' => $allSales,
        ];
    }

    protected function calculateMonthOverMonth($data, $month, $previousMonth)
    {
        $currentMonthData = $data[$month]['price'];
        
        if (isset($data[$previousMonth]) && $data[$previousMonth]['price'] !== 0) {
            $previousMonthData = $data[$previousMonth]['price'];
            $monthOverMonth = ($currentMonthData - $previousMonthData) / $previousMonthData * 100;
            return $monthOverMonth;
        } else {
            return null; // 前月のデータが存在しないか、前月のデータがゼロの場合
        }
    }

    //-------------------------------------------------------------------------
    /**
     *
     *
     */
    protected function getPrimeMemberCount(){
        $qb = $this->customerRepository->createQueryBuilder('c')
            ->select('count(c) as count')
            ->andWhere('c.prime_member = 1')
            ;
        $q = $qb->getQuery();

        $result = [];
        try {
            $tmp = $q->getResult();

            foreach($tmp as $item0){
                $item = new \ArrayObject($item0, \ArrayObject::ARRAY_AS_PROPS);
                return $item->count;
            }

        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return 0;        
    }
    /**
     *
     *
     */
    protected function getPrimeLightMemberCount(){
        $qb = $this->customerRepository->createQueryBuilder('c')
            ->select('count(c) as count')
            ->andWhere('c.prime_member = 2')
            ;
        $q = $qb->getQuery();

        $result = [];
        try {
            $tmp = $q->getResult();

            foreach($tmp as $item0){
                $item = new \ArrayObject($item0, \ArrayObject::ARRAY_AS_PROPS);
                return $item->count;
            }

        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return 0;        
    }
    
    /**
     * @param \DateTime $dateTime
     *
     * @return array|mixed
     *
     *
     */
    protected function _getSaleTypeSalesByDay($dateTime){
    
        //午前9時以前の集計は前日から開始 20211124 kikuzawa
        $h = $dateTime->format('H');
        if($h < 9) $dateTime->modify('-1 day');

        $dateTimeStart = clone $dateTime;
        $dateTimeStart->setTime(9, 0, 0, 0);// 9時から集計 20211119 kikuzawa

        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 days');

        $qb = $this->orderRepository
                ->createQueryBuilder('o')
                ->select('
                    o.id as order_id,
                    o.payment_total,
                    oi.price,
                    oi.quantity,
                    p.id as product_id,
                    st.id as sale_type_id,
                    st.name as sale_name
                ')
                ->join('o.OrderItems', 'oi')
                ->join('oi.Product', 'p')
                ->join('oi.ProductClass', 'dp')
                ->join('dp.SaleType','st')
                ->setParameter(':excludes', $this->excludes)
                ->setParameter(':targetDateStart', $dateTimeStart)
                ->setParameter(':targetDateEnd', $dateTimeEnd)
                ->andWhere(':targetDateStart <= o.order_date and o.order_date < :targetDateEnd')
                ->andWhere('o.OrderStatus NOT IN (:excludes)');

        $q = $qb->getQuery();

        $result = [];
        try {
            $tmp = $q->getResult();

            foreach($tmp as $item0){
                $item = new \ArrayObject($item0, \ArrayObject::ARRAY_AS_PROPS);
                if(!isset($result[$item->sale_type_id])){
                    $result[$item->sale_type_id] = ["count"=>0, "price"=>0, "order_count"=>0, "order"=>[],"sale_name"=>$item->sale_name];
                }
                
                $result[$item->sale_type_id]["count"] += $item->quantity;
                if(!isset($result[$item->sale_type_id]["order"][$item->order_id])){

                    $result[$item->sale_type_id]["price"] += $item->payment_total;

                    $result[$item->sale_type_id]["order"][$item->order_id] = true;
                    $result[$item->sale_type_id]["order_count"] += 1;
                }
            }

        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return $result;
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return array|mixed
     *
     *
     */
    protected function getSaleTypeSalesByDay($dateTime){
    
        //午前9時以前の集計は前日から開始 20211124 kikuzawa
        $h = $dateTime->format('H');
        if($h < 9) $dateTime->modify('-1 day');

        $dateTimeStart = clone $dateTime;
        $dateTimeStart->setTime(9, 0, 0, 0);// 9時から集計 20211119 kikuzawa

        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 days');

        $qb = $this->orderRepository
            ->createQueryBuilder('o')
            ->select('
            o.payment_method,
            SUM(o.payment_total) AS order_amount,
            COUNT(o) AS order_count')
            ->setParameter(':excludes', $this->excludes)
            ->setParameter(':targetDateStart', $dateTimeStart)
            ->setParameter(':targetDateEnd', $dateTimeEnd)
            ->andWhere(':targetDateStart <= o.order_date and o.order_date < :targetDateEnd')
            ->andWhere('o.OrderStatus NOT IN (:excludes)')
            ->andWhere('o.payment_method not like :amazon')
            ->andWhere('o.payment_method not like :rakuten')
            ->andWhere('o.payment_method not like :qoo')
            ->setParameter(':amazon', "%アマゾン%")
            ->setParameter(':rakuten', "%楽天%")
            ->setParameter(':qoo', "%qoo%")
            ->groupBy('o.payment_method')
            ->orderBy('o.payment_method')
            ;
        $q = $qb->getQuery();

        $result = [];
        try {
            $result = $q->getResult();
        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return $result;
    }


 /**
     * @param \DateTime $dateTime
     *
     * @return array|mixed
     *
     *
     */
    protected function _getSaleTypeSalesByMonth($dateTime){
    
        $dateTimeStart = clone $dateTime;
        $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 months');

        $qb = $this->orderRepository
                ->createQueryBuilder('o')
                ->select('
                    o.id as order_id,
                    o.payment_total,
                    oi.price,
                    oi.quantity,
                    p.id as product_id,
                    st.id as sale_type_id,
                    st.name as sale_name
                ')
                ->join('o.OrderItems', 'oi')
                ->join('oi.Product', 'p')
                ->join('oi.ProductClass', 'dp')
                ->join('dp.SaleType','st')
                ->setParameter(':excludes', $this->excludes)
                ->setParameter(':targetDateStart', $dateTimeStart)
                ->setParameter(':targetDateEnd', $dateTimeEnd)
                ->andWhere(':targetDateStart <= o.order_date and o.order_date < :targetDateEnd')
                ->andWhere('o.OrderStatus NOT IN (:excludes)');

        $q = $qb->getQuery();

        $result = [];
        try {
            $tmp = $q->getResult();

            foreach($tmp as $item0){
                $item = new \ArrayObject($item0, \ArrayObject::ARRAY_AS_PROPS);
                if(!isset($result[$item->sale_type_id])){
                    $result[$item->sale_type_id] = ["count"=>0, "price"=>0, "order_count"=>0, "order"=>[],"sale_name"=>$item->sale_name];
                }
                $result[$item->sale_type_id]["count"] += $item->quantity;
                if(!isset($result[$item->sale_type_id]["order"][$item->order_id])){
                    $result[$item->sale_type_id]["price"] += $item->payment_total;
                    $result[$item->sale_type_id]["order"][$item->order_id] = true;
                    $result[$item->sale_type_id]["order_count"] += 1;
                }
            }

        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }
        return $result;
    }

 /**
     * @param \DateTime $dateTime
     *
     * @return array|mixed
     *
     *
     */
    protected function getSaleTypeSalesByMonth($dateTime){
    
        $dateTimeStart = clone $dateTime;
        $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 months');

        $qb = $this->orderRepository
            ->createQueryBuilder('o')
            ->select('
            o.payment_method,
            SUM(o.payment_total) AS order_amount,
            COUNT(o) AS order_count')
            ->setParameter(':excludes', $this->excludes)
            ->setParameter(':targetDateStart', $dateTimeStart)
            ->setParameter(':targetDateEnd', $dateTimeEnd)
            ->andWhere(':targetDateStart <= o.order_date and o.order_date < :targetDateEnd')
            ->andWhere('o.OrderStatus NOT IN (:excludes)')
            ->andWhere('o.payment_method not like :amazon')
            ->andWhere('o.payment_method not like :rakuten')
            ->andWhere('o.payment_method not like :qoo')
            ->setParameter(':amazon', "%アマゾン%")
            ->setParameter(':rakuten', "%楽天%")
            ->setParameter(':qoo', "%qoo%")
            ->groupBy('o.payment_method')
            ->orderBy('o.payment_method')
            ;
        $q = $qb->getQuery();

        $result = $q->getResult();

        return $result;
    }
    //-------------------------------------------------------------------------
    /**
     * @param \DateTime $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getSalesByDay($dateTime)
    {
        //午前9時以前の集計は前日から開始 20211124 kikuzawa
        $h = $dateTime->format('H');
        if($h < 9) $dateTime->modify('-1 day');

        $dateTimeStart = clone $dateTime;
        $dateTimeStart->setTime(9, 0, 0, 0);// 9時から集計 20211119 kikuzawa

        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 days');

        $qb = $this->orderRepository
            ->createQueryBuilder('o')
            ->select('
            SUM(o.payment_total) AS order_amount,
            COUNT(o) AS order_count')
            ->setParameter(':excludes', $this->excludes)
            ->setParameter(':targetDateStart', $dateTimeStart)
            ->setParameter(':targetDateEnd', $dateTimeEnd)
            ->andWhere(':targetDateStart <= o.order_date and o.order_date < :targetDateEnd')
            ->andWhere('o.OrderStatus NOT IN (:excludes)');
        $q = $qb->getQuery();

        $result = [];
        try {
            $result = $q->getSingleResult();
        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return $result;
    }

    // 当日集計した受注から商品個数を取得 20211119 kikuzawa
    /**
     * @param \DateTime $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getQuantityByDay($dateTime)
    {
        //午前9時以前の集計は前日から開始 20211124 kikuzawa
        $h = $dateTime->format('H');
        if($h < 9) $dateTime->modify('-1 day');

        $dateTimeStart = clone $dateTime;
        $dateTimeStart->setTime(9, 0, 0, 0);// 9時から集計 20211119 kikuzawa

        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 days');

        $qb = $this->orderRepository
            ->createQueryBuilder('o')
            ->select('
            SUM(oi.quantity) AS total_quantity')
            ->leftJoin('o.OrderItems', 'oi')
            ->setParameter(':excludes', $this->excludes)
            ->setParameter(':targetDateStart', $dateTimeStart)
            ->setParameter(':targetDateEnd', $dateTimeEnd)
            ->andWhere(':targetDateStart <= o.order_date and o.order_date < :targetDateEnd')
            ->andWhere('o.OrderStatus NOT IN (:excludes)')
            ->andWhere("oi.product_name NOT LIKE '%セット%' AND oi.product_code IS NOT NULL");
        $q = $qb->getQuery();

        $result = [];
        try {
            $result = $q->getSingleResult();
        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return $result;
    }

    // 今月集計した受注から商品個数を取得 20211119 kikuzawa
    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getTotalQuantityForMonth($dateTime)
    {
        $dateTimeStart = clone $dateTime;
        $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 months');

        $qb = $this->orderRepository
            ->createQueryBuilder('o')
            ->select('SUM(oi.quantity) AS total_quantity')
            ->leftJoin('o.OrderItems', 'oi')
            ->setParameter(':excludes', $this->excludes)
            ->setParameter(':targetDateStart', $dateTimeStart)
            ->setParameter(':targetDateEnd', $dateTimeEnd)
            ->andWhere('o.order_date >= :targetDateStart')
            ->andWhere('o.order_date < :targetDateEnd')
            ->andWhere('o.OrderStatus NOT IN (:excludes)')
            ->andWhere("oi.product_name NOT LIKE '%セット%' AND oi.product_code IS NOT NULL");
        $q = $qb->getQuery();

        $result = [];
        try {
            $result = $q->getSingleResult();
        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return $result;
    }

    // 今月のログイン数
    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getLoginUsersByMonth($dateTime)
    {
        $dateTimeStart = clone $dateTime;
        $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 months');

        $sql = 'SELECT * FROM dtb_customer WHERE last_login_date >= :start AND last_login_date < :end';
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute([
            'start' => $dateTimeStart->format('Y-m-d H:i:s'),
            'end' => $dateTimeEnd->format('Y-m-d H:i:s')
        ]);
        $result = $stmt->fetchAll();
    
        return count($result);


        // $qb = $this->customerRepository
        //     ->createQueryBuilder('c')
        //     ->setParameter(':start', $dateTimeStart)
        //     ->setParameter(':end', $dateTimeEnd)
        //     ->andWhere('c.last_login_date >= :start')
        //     ->andWhere('c.last_login_date < :end');

        // $result = $qb->getQuery()->getResult();
        // $recordCount = count($result);
        // return $recordCount;
    }

    // 今月の購入者数
    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getPurchasersForMonth($dateTime)
    {
        $dateTimeStart = clone $dateTime;
        $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 months');

        $sql = 'SELECT customer_id, COUNT(customer_id) as customer_num FROM dtb_order WHERE phone_number IS NOT NULL AND phone_number != "00000000000" AND  payment_date IS NOT NULL AND payment_date >= :start AND payment_date < :end GROUP BY customer_id';
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute([
            'start' => $dateTimeStart->format('Y-m-d H:i:s'),
            'end' => $dateTimeEnd->format('Y-m-d H:i:s')
        ]);
        $result = $stmt->fetchAll();
    
        return count($result);


        // $qb = $this->orderRepository
        //     ->createQueryBuilder('o')
        //     ->select('o.customer_id, COUNT(o.customer_id) AS customer_num')
        //     ->andWhere('o.phone_number IS NOT NULL')
        //     ->andWhere('o.phone_number !="00000000000"')
        //     ->andWhere('o.payment_date IS NOT NULL')
        //     ->andWhere('o.payment_date  >= :start')
        //     ->andWhere('o.payment_date  < :end')
        //     ->andWhere('o.OrderStatus NOT IN (:excludes)')
        //     ->setParameter(':start', $dateTimeStart)
        //     ->setParameter(':end', $dateTimeEnd)
        //     ->setParameter(':excludes', $this->excludes)
        //     ->groupBy('o.customer_id');

        // $result = $qb->getQuery()->getResult();
        // $recordCount = count($result);
        // return $recordCount;
    }

    // 年計の購入者数
    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getYearlyPurchasersForMonth($dateTime)
    {
        $dateTimeStart = clone $dateTime;
        $dateTimeStart->modify('-1 year')->modify('first day of this month')->setTime(0, 0, 0, 0);
        $dateTimeEnd = clone $dateTime;
        $dateTimeEnd->modify('first day of this month')->setTime(0, 0, 0, 0);
        
        $sql = 'SELECT COUNT(DISTINCT customer_id) as customer_num FROM dtb_order WHERE phone_number IS NOT NULL AND phone_number != "00000000000" AND payment_date IS NOT NULL AND payment_date >= :start AND payment_date < :end';
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute([
            'start' => $dateTimeStart->format('Y-m-d H:i:s'),
            'end' => $dateTimeEnd->format('Y-m-d H:i:s')
        ]);
        $result = $stmt->fetchColumn();
        return $result;

        
        // $qb = $this->orderRepository
        //     ->createQueryBuilder('o')
        //     ->select('o.customer_id, COUNT(o.customer_id) AS customer_num')
        //     ->andWhere('o.phone_number IS NOT NULL')
        //     ->andWhere('o.phone_number !="00000000000"')
        //     ->andWhere('o.payment_date IS NOT NULL')
        //     ->andWhere('o.payment_date  >= :start')
        //     ->andWhere('o.payment_date  < :end')
        //     ->andWhere('o.OrderStatus NOT IN (:excludes)')
        //     ->setParameter(':start', $dateTimeStart)
        //     ->setParameter(':end', $dateTimeEnd)
        //     ->setParameter(':excludes', $this->excludes)
        //     ->groupBy('o.customer_id');

        // $result = $qb->getQuery()->getResult();
        // $recordCount = count($result);
        // return $recordCount;
    }

     // 今月の出荷数
    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getMonthlyShipmentsForMonth($dateTime)
    {
        $dateTimeStart = clone $dateTime;
        $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 months');

        $sql = 'SELECT * FROM dtb_shipping WHERE tracking_number IS NOT NULL AND create_date >= :start AND create_date < :end';
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute([
            'start' => $dateTimeStart->format('Y-m-d H:i:s'),
            'end' => $dateTimeEnd->format('Y-m-d H:i:s')
        ]);
        $result = $stmt->rowCount();
        return $result;
    }

     // 今月の出荷数
    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getYearlyShipmentsForMonth($dateTime)
    {
        $dateTimeEnd = clone $dateTime;
        $dateTimeEnd->modify('first day of this month');//->setTime(0, 0, 0, 0);
        
        // 1年前の日付を計算
        $dateTimeStart = clone $dateTimeEnd;
        $dateTimeStart->modify('-1 year');
        
        $sql = 'SELECT * FROM dtb_shipping WHERE tracking_number IS NOT NULL AND create_date >= :start AND create_date < :end';
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute([
            'start' => $dateTimeStart->format('Y-m-d H:i:s'),
            'end' => $dateTimeEnd->format('Y-m-d H:i:s')
        ]);
        $result = $stmt->fetchAll();
        return count($result);
    }

     // 稼働顧客の平均在籍期間
    /**
     *
     * @return integer
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getAverageTenureOfActiveCustomers()
    {
        $sql = 'SELECT * FROM dtb_customer';//WHERE phone_number IS NOT NULL AND phone_number != "00000000000"'
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $currentDate = date("Y-m-d H:i:s");
        $totalYears = 0;
        $validUserCount = 0;
        
        $totalYears = 0;
        $validUserCount = 0;

        foreach ($result as $row) {
            $createDate = strtotime($row['create_date']);
            $currentDate = time(); // 現在のUNIXタイムスタンプを取得

            $secondsInYear = 60 * 60 * 24 * 365; // 1年の秒数

            $diffInSeconds = $currentDate - $createDate;
            $years = floor($diffInSeconds / $secondsInYear);

            $totalYears += $years;
            $validUserCount++;
        }

        if ($validUserCount > 0) {
            $averageYears = $totalYears / $validUserCount;
            return round($averageYears, 2);
        } else {
            return 0;
        }
    }

    /**
     *
     *
     */
    protected function cache($name, $callback, $expire=600){
        $dir = realpath(__DIR__ . "/../../../../var/cache/prod/");
        $cache_file = $dir ."/" . $name . ".cache";
        if(file_exists($cache_file)){
            if(filemtime($cache_file) + $expire > time()){
                $enc_data = file_get_contents($cache_file);
                if($enc_data){
                    return unserialize($enc_data);
                }
            }
        }
        $data = $callback();
        file_put_contents($cache_file, serialize($data));
        return $data;
    }

    // 毎月の配布ポイント数
    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getPointData($dateTime, $format, $isDeliveryPoint = true)
    {
        //アマゾン売上振込用: customer_id 14033
        //楽天売上振込用: 14032
        //qoo10売上振込用:14031
        $dateTimeStart;
        $dateTimeEnd;
        if($format == "Y/m/d"){
            $dateTimeStart = clone $dateTime;
            $dateTimeStart->setTime(0, 0, 0, 0);
            $dateTimeEnd = clone $dateTimeStart;
            $dateTimeEnd->modify('+1 day');
        }elseif($format == "Y/m"){
            $dateTimeStart = clone $dateTime;
            $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
            $dateTimeEnd = clone $dateTimeStart;
            $dateTimeEnd->modify('+1 months');
        }elseif($format == "Y"){
            $dateTimeStart = clone $dateTime;
            $dateTimeStart->modify('first day of January')->setTime(0, 0, 0, 0);
            $dateTimeEnd = clone $dateTimeStart;
            $dateTimeEnd->modify('+1 year');
        }

        $qb = $this->pointLogRepository
            ->createQueryBuilder('po')
            ->select('SUM(po.point2 - po.point1) AS point')
            ->andWhere($isDeliveryPoint ? 'po.point2 > po.point1' : 'po.point2 < po.point1')
            ->andWhere('po.create_date >= :dateFrom')
            ->andWhere('po.create_date < :dateTo')
            ->andWhere('po.customer_id NOT IN (14031, 14032, 14033)')
            ->setParameter(':dateFrom', $dateTimeStart)
            ->setParameter(':dateTo', $dateTimeEnd);

        $q = $qb->getQuery();

        $result = [];
        try {
            $result = $q->getSingleResult();
        } catch (NoResultException $e) {
        }

        $point = (int)$result['point'];
        return abs($point);
    }

    // ポイント内訳
    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getPointDataDetail($dateTime, $format)
    {
        $dateTimeStart;
        $dateTimeEnd;
        if($format == "Y/m/d"){
            $dateTimeStart = clone $dateTime;
            $dateTimeStart->setTime(0, 0, 0, 0);
            $dateTimeEnd = clone $dateTimeStart;
            $dateTimeEnd->modify('+1 day');
        }elseif($format == "Y/m"){
            $dateTimeStart = clone $dateTime;
            $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
            $dateTimeEnd = clone $dateTimeStart;
            $dateTimeEnd->modify('+1 months');
        }elseif($format == "Y"){
            $dateTimeStart = clone $dateTime;
            $dateTimeStart->modify('first day of January')->setTime(0, 0, 0, 0);
            $dateTimeEnd = clone $dateTimeStart;
            $dateTimeEnd->modify('+1 year');
        }

        $qb = $this->pointLogRepository
            ->createQueryBuilder('po')
            ->select('po.memo, SUM(po.point2 - po.point1) AS total_point')
            ->andWhere('po.point2 > po.point1')
            ->andWhere('po.create_date >= :dateFrom')
            ->andWhere('po.create_date < :dateTo')
            ->andWhere('po.customer_id NOT IN (14031, 14032, 14033)') //アマゾン、楽天、qoo10売上振込アカウントは除く
            ->setParameter(':dateFrom', $dateTimeStart)
            ->setParameter(':dateTo', $dateTimeEnd)
            ->groupBy('po.memo');

        $q = $qb->getQuery();

        $detail = [
            "ギフト" => 0,
            "ログインポイント" => 0,
            "動画視聴" => 0,
            "ページ閲覧ポイント" => 0,
            "店頭付与" => 0,
        ];
        try {

            $result = $q->getResult();
            foreach($result as $record){
                $memo = $this->getMemoStr($record["memo"]);
                
                if($memo == "ログインポイント" 
                    || $memo == "動画視聴" 
                    || $memo == "ページ閲覧ポイント" 
                    || $memo == "ギフト"
                    || $memo == "店頭付与"
                    ){
                    $detail[$memo] =   (int)$record["total_point"];
                }
            }

        } catch (NoResultException $e) {
        }
        return $detail;
    }

    public function getMemoStr($memo){

        if(preg_match('/add_login_point/', $memo)){
            return "ログインポイント";
        }
        if(preg_match('/PointProcessor.php/', $memo)){
            return "ポイント利用";
        }
        if(preg_match('/PointHelper.php/', $memo)){
            return "ポイント付与";
        }
        if(preg_match('/PaymentNotificationController.php/', $memo)){
            return "ファミリーライト送料還元";
        }
        if(preg_match('/reward/', $memo)){
            return "ページ閲覧ポイント";
        }
        if(preg_match('/AutoGift/', $memo)){
            return "ギフト";
        }
        if(preg_match('/VideoController/', $memo)){
            return "動画視聴";
        }
        
        if(preg_match('/ShopEventPointController/', $memo)){
            return "店頭付与";
        }
        //CustomizeShopEventPointController
        if(preg_match('/CustomizeShopEventPointController/', $memo)){
            return "店頭付与";
        }
        if(preg_match('/PageCountdownService/', $memo)){
            return "カウントダウン";
        }

        return "";
    }

    /**
     * @return int|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getTotalPoint()
    {
        $excludedIds = [14031, 14032, 14033];

        $qb = $this->customerRepository
            ->createQueryBuilder('c')
            ->select('SUM(c.point) AS sum_point')
            ->where('c.id NOT IN (:excludedIds)')
            ->setParameter('excludedIds', $excludedIds)
            ->getQuery();

        // クエリを実行して合計ポイントを取得
        $totalPoint = (int) $qb->getSingleScalarResult();

        return $totalPoint;
    }


    /**
     * アクセス人数集計
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/daily_access_stats", name="admin_homepage_daily_access")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function dailyAccessStats(Request $request)
    {   
        Carbon::setLocale('Asia/Tokyo');
        $time = $request->query->get('time');
        $labels = [];
        $formatTime = 'Y/m/d';
        switch ($time) {
            case 'week':
                $start = Carbon::now()->subDays(6)->setTime(0, 0, 0);
                $end = Carbon::now()->endOfDay();
                $formatTime = 'Y/m/d';
                $functionKey = 'addDay';
                break;

            case 'year':
                $start = Carbon::now()->month(1)->day(1);
                $end = Carbon::now()->endOfYear();
                $formatTime = 'Y/m';
                $functionKey = 'addMonthNoOverflow';
                break;

            case 'annual':
                $start = Carbon::now()->subYears(5)->startOfYear();
                $end = Carbon::now()->endOfYear();
                $formatTime = 'Y';
                $functionKey = 'addYear';
                break;

            default:
                $start = Carbon::now()->day(1);
                $end = Carbon::now()->endOfMonth();
                $formatTime = 'Y/m/d';
                $functionKey = 'addDay';
                break;
        }
        
        $accessCounts = [];
        $accessCounts2 = [];
        $loginUserCountDataset = [];
        $nonLoginUserCountDataset = [];
        $accessCounts = $this->getFetchAccessCounts($start, $end, $formatTime);
        $accessCounts2 = $this->getFetchAccessCounts2($start, $end, $formatTime);
        $startTime = $start->copy()->startOfDay()->setTimezone('Asia/Tokyo');
        $endTime = $end->copy()->startOfDay()->setTimezone('Asia/Tokyo');
        for ($startTime; $startTime <= $endTime; $startTime->{$functionKey}()) {
            $datetime = $startTime->format($formatTime);
            $labels[] = $datetime;
            $loginUserCountDataset[$datetime] = 0;
            $nonLoginUserCountDataset[$datetime] = 0;
        }

        foreach ($accessCounts as $accessCount) {
            $datetime = $accessCount['access_date'];
            $loginUserCountDataset[$datetime] = $accessCount['access_count'];
        }

        foreach ($accessCounts2 as $accessCount) {
            $datetime = $accessCount['access_date'];
            $nonLoginUserCountDataset[$datetime] = $accessCount['access_count'];
        }

        return $this->json([
            'loginUserCount' => array_values($loginUserCountDataset),
            'noneLoginUserCount' => array_values($nonLoginUserCountDataset),
            'labels' => $labels,
        ]);
    }


    /**
     * 売上状況の取得
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/ltv", name="admin_homepage_ltv")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getLtv(Request $request)
    {
        $elm = $this;

        $results = $this->cache('CustomizeAdminController.ltv', function() use($elm){ 
            $numberOfMonths = 13;//２年分

            // 月間の売上高
            $toDate = Carbon::now();
            $fromDate = Carbon::now()->subYear(1)->startOfMonth();
            $saleTypeYear = $elm->getSaleTypeData($fromDate, $toDate, 'Y/m');

            $toDate = Carbon::now();
            $fromDate = Carbon::now()->subYear(1)->startOfMonth();
            $rawYear = $elm->getData($fromDate, $toDate, 'Y/m', true);
            $rawYear = array_reverse($rawYear);

            //年計の売上高
            $now = Carbon::now();
            $yearSaleForEachMonth = [];
            for($i = 0; $i < $numberOfMonths; $i++){
                $toDate = $now->copy()->subMonths($i);
                $fromDate = $now->copy()->subYear()->subMonths($i)->startOfMonth();   
                $rawData = $elm->getData($fromDate, $toDate, 'Y/m', true);
                array_push($yearSaleForEachMonth, $rawData);
            }

            $yearSaleForEachMonth2 = [];
            foreach($yearSaleForEachMonth as $yearSale){
                $sum = 0;
                $key = "";
                foreach($yearSale as $k=>$v){
                    $key = $k;
                    $monthSale = $v;
                    $sum = $sum + $monthSale["price"];
                }
                array_push($yearSaleForEachMonth2,$sum);
            }
            $yearSaleForEachMonth2 = array_reverse($yearSaleForEachMonth2);

            //月間稼動顧客数 mukai
            $purchasersEachMonth = [];
            for ($i = 0; $i < $numberOfMonths; $i++) {
                $date = new \DateTime('-' . $i . ' month');
                $purchasersEachMonth[] = $this->getPurchasersForMonth($date);
            }
            $purchasersEachMonth = array_reverse($purchasersEachMonth);

            //年間稼動顧客数 mukai
            $yearPurchasersEachMonth = [];
            for ($i=0; $i<$numberOfMonths; $i++) {
                $date = new \DateTime('-' . $i . ' month');
                $yearPurchasersEachMonth[] = $this->getYearlyPurchasersForMonth($date);
            }
            $yearPurchasersEachMonth = array_reverse($yearPurchasersEachMonth);

            //毎月の売上個数 mukai
            $quantityEachMonth = [];
            for ($i=0; $i<$numberOfMonths; $i++) {
                $date = new \DateTime('-' . $i . ' month');
                $quantityEachMonth[] = $this->getTotalQuantityForMonth($date);
            }
            $quantityEachMonth = array_reverse($quantityEachMonth);

            //月間出荷数 mukai
            $shipmentsEachMonth = [];
            for ($i=0; $i<$numberOfMonths; $i++) {
                $date = new \DateTime('-' . $i . ' month');
                $shipmentsEachMonth[] = $this->getMonthlyShipmentsForMonth($date);
            }
            $shipmentsEachMonth = array_reverse($shipmentsEachMonth);

            //年間出荷数　mukai
            $shipmentsYearEachMonth = [];
            for ($i=0; $i<$numberOfMonths; $i++) {
                $date = new \DateTime('-' . $i . ' month');
                $shipmentsYearEachMonth[] = $this->getYearlyShipmentsForMonth($date);
            }
            $shipmentsYearEachMonth = array_reverse($shipmentsYearEachMonth);

            //月間送料 mukai
            $shippingFeeForEachMonth = [];
            for ($i=0; $i<$numberOfMonths; $i++) {
                $shippingFee = ($shipmentsEachMonth[$i] * 880);
                array_push($shippingFeeForEachMonth, $shippingFee);
            }

            //購買単価を算出
            $purchsingPrice = [];
            for ($i = 0; $i < $numberOfMonths; $i++) {
                $sale = $yearSaleForEachMonth2[$i];
                $shipment = $shipmentsYearEachMonth[$i];
                
                $price;
                if ($shipment != 0) {
                    $price = round($sale / $shipment, 1);
                } else {
                    $price = 0.0; // ゼロ除算を防ぐためにデフォルトで0をセット
                }

                $purchsingPrice[] = $price;
            }

            //年間LTVを算出 mukai
            $yearLtv = [];
            for($i = 0; $i < $numberOfMonths; $i++){
                $ltv = 0;
                $sale = $yearSaleForEachMonth2[$i];
                $purchaser = $yearPurchasersEachMonth[$i];
                if($purchaser > 0){
                    $ltv = $sale/$purchaser;
                }
                array_push($yearLtv, $ltv);
            }
            
            $averageYears = $this->getAverageTenureOfActiveCustomers();
            $ltv = round($yearLtv[count($yearLtv) - 1] * $averageYears, 1);
            $pointAcquisitionUser = $this->getLoginUsersByMonth(new \DateTime('-'. (string)'0' .' month'));

            return [
                //mukai
                'quantityEachMonth' => $quantityEachMonth,
                'loginUserCount' => $pointAcquisitionUser,
                'purchasersEachMonth' => $purchasersEachMonth,
                'yearPurchasersEachMonth' => $yearPurchasersEachMonth,
                'shipmentsEachMonth' => $shipmentsEachMonth,
                'shipmentsYearEachMonth' => $shipmentsYearEachMonth,
                'shippingFeeForEachMonth' => $shippingFeeForEachMonth,
                'saleForEachMonth' => $rawYear,
                'yearSaleForEachMonth'=> $yearSaleForEachMonth2,
                'purchsingPrice' => $purchsingPrice,
                'yearLtv' => $yearLtv,
                'ltv' => $ltv,
                'averageYears' => $averageYears,
                'point' => $point,
            ];
        }, 60*60*24);
        return $this->json($results);
    }

    /**
     * 売上状況の取得
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/popularItems", name="admin_homepage_popular_items")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getPopularItems(Request $request){
            
            $elm = $this;

            $popularItems = $this->cache('CustomizeAdminController.popularItems', function() use($elm){ 
                $dateTimeStart = new \DateTime();
                $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
                $dateTimeEnd = clone $dateTimeStart;
                $dateTimeEnd->modify('+1 months');
    
                $qb = $this->orderRepository
                ->createQueryBuilder('o')
                ->join('o.OrderItems', 'oi')
                ->join('oi.Product', 'p')
                ->join('oi.ProductClass', 'dp')
                ->andWhere('o.order_date IS NOT NULL')
                ->andWhere('o.order_date >= :targetDateStart')
                ->andWhere('o.order_date < :targetDateEnd')
                ->andWhere('p.name NOT LIKE :excludeWord')
                ->andWhere('p.id NOT IN (759, 760, 761)') //アマゾン、楽天、qoo10の売上商品は除く
                ->setParameter(':targetDateStart', $dateTimeStart)
                ->setParameter(':targetDateEnd', $dateTimeEnd)
                ->setParameter(':excludeWord', "%専用%") //TikTok関係の商品も除く
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->groupBy('p.id, p.name')
                ->orderBy('total_sale', 'DESC')
                ->setMaxResults(30)
                ->select('o.order_date, p.id, p.name, SUM(oi.price * oi.quantity) AS total_sale, SUM(oi.quantity) AS total_quantity, dp.price02 as price');
                // return
                // $popularItems =  $qb->getQuery()->getResult();
                return $qb->getQuery()->getResult();
            }, 60*10);
            return $this->json($popularItems);
    }

    /**
     * 売上状況の取得
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/point", name="admin_homepage_point")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getPointGraphData(Request $request)
    {
        $numberOfMonths = 13;//２年分
        $weeklyPoints = [];
        for ($i=0; $i<7; $i++) {
            $date = new \DateTime('-' . $i . ' day');
            $dataDetails["総配布ポイント"] = $this->getPointData($date, "Y/m/d");
            $dataDetails["総消費ポイント"] = $this->getPointData($date, "Y/m/d", false);
            $details = array_merge_recursive($dataDetails, $this->getPointDataDetail($date, 'Y/m/d'));
            $weeklyPoints[] = 
            [
                "date" => $date->format('Y/m/d'),
                "data" => $details
            ];
        }

        $monthPoints = [];
        $numberOfDaysInThisMonth = date('t');
        $firstDayOfThisMonth = new \DateTime('first day of this month');
        $lastDayOfThisMonth = new \DateTime('last day of this month');

        for ($i = 0; $i < $numberOfDaysInThisMonth; $i++) {
            $date = clone $lastDayOfThisMonth;
            $date->modify('-' . $i . ' day');
            $dataDetails["総配布ポイント"] = $this->getPointData($date, "Y/m/d");
            $dataDetails["総消費ポイント"] = $this->getPointData($date, "Y/m/d", false);
            $details = array_merge_recursive($dataDetails, $this->getPointDataDetail($date, 'Y/m/d'));
            $monthPoints[] = [
                "date" => $date->format('Y/m/d'),
                "data" => $details
            ];
        }

        $yearPoints = [];
        for ($i=0; $i<$numberOfMonths; $i++) {
            $date = new \DateTime('-' . $i . ' month');
            $dataDetails["総配布ポイント"] = $this->getPointData($date, "Y/m");
            $dataDetails["総消費ポイント"] = $this->getPointData($date, "Y/m", false);
            $details = array_merge_recursive($dataDetails, $this->getPointDataDetail($date, 'Y/m'));

            $yearPoints[] = 
            [
                "date" => $date->format('Y/m'),
                "data" => $details
            ];
        }

        $annualYearPoints = [];
        for ($i=0; $i<10; $i++) {
            $date = new \DateTime('-' . $i . ' year');
            $dataDetails["総配布ポイント"] = $this->getPointData($date, "Y");
            $dataDetails["総消費ポイント"] = $this->getPointData($date, "Y", false);
            $details = array_merge_recursive($dataDetails, $this->getPointDataDetail($date, 'Y'));
            $annualYearPoints[] = 
            [
                "date" => $date->format('Y'),
                "data" => $details
            ];
        }

        $weeklyPoints = array_reverse($weeklyPoints);
        $monthPoints = array_reverse($monthPoints);
        $yearPoints = array_reverse($yearPoints);
        $annualYearPoints = array_reverse($annualYearPoints);
        $totalPoint = $this->getTotalPoint();

        return $this->json([
            "totalPoint" => $totalPoint,
            "graphData" => [$weeklyPoints, $monthPoints, $yearPoints, $annualYearPoints],
        ]);
    }

     /**
     * 特定の商品の注文一覧を取得する
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/order_by_product", name="admin_homepage_order_by_product")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getOrderByProduct(Request $request)
    {   
        $itemId = $request->get('item_id');

        $dateTimeStart = new \DateTime();
        $dateTimeStart->modify('first day of this months')->setTime(0, 0, 0, 0);
        $dateTimeEnd = clone $dateTimeStart;
        $dateTimeEnd->modify('+1 months');
    
        $qb = $this->orderRepository
            ->createQueryBuilder('o')
            ->join('o.OrderItems', 'oi')
            ->join('oi.Product', 'p')
            ->andWhere('o.order_date >= :dateFrom')
            ->andWhere('o.order_date < :dateTo')
            ->andWhere('p.id = :productId')
            ->setParameter(':dateFrom', $dateTimeStart)
            ->setParameter(':dateTo', $dateTimeEnd)
            ->setParameter(':productId', $itemId)
            ->andWhere('o.OrderStatus NOT IN (:excludes)')
            ->setParameter(':excludes', $this->excludes)
            ->orderBy('o.order_date', 'DESC')
            ->select('o.id, o.payment_method, o.order_date, o.name01, o.name02, o.payment_total, p.name, p.id as product_id');
        
        $results = $qb->getQuery()->getResult();
        foreach ($results as &$result) {
            $result['order_date'] = $result['order_date']->format('Y-m-d H:i:s'); // ここでフォーマット
        }

        return $this->json($results);
    }
    
    protected function getFetchAccessCounts($startDate, $endDate, $formatTime)
    {
        switch ($formatTime) {
            case 'Y/m/d':
                $sqlFormat = "DATE_FORMAT(al.create_date, '%Y/%m/%d')";
                break;
            case 'Y/m':
                $sqlFormat = "DATE_FORMAT(al.create_date, '%Y/%m')";
                break;
            case 'Y':
                $sqlFormat = "DATE_FORMAT(al.create_date, '%Y')";
                break;
            default:
                $sqlFormat = "DATE_FORMAT(al.create_date, '%Y/%m/%d')";
                break;
        }

        $qb = $this->accessLogRepository->createQueryBuilder('al')
        ->where('al.create_date BETWEEN :start_date AND :end_date')
        ->andWhere('al.customer_id IS NOT NULL')
        ->select("DATE_FORMAT(al.create_date, $sqlFormat) AS access_date, COUNT(al.id) AS access_count")
        ->setParameter('start_date', $startDate)
        ->setParameter('end_date', $endDate)
        ->groupBy('access_date')
        ->orderBy('access_date', 'ASC');
        return $qb->getQuery()->getResult();
    }

    protected function getFetchAccessCounts2($startDate, $endDate, $formatTime)
    {
        switch ($formatTime) {
            case 'Y/m/d':
                $sqlFormat = "DATE_FORMAT(al.create_date, '%Y/%m/%d')";
                break;
            case 'Y/m':
                $sqlFormat = "DATE_FORMAT(al.create_date, '%Y/%m')";
                break;
            case 'Y':
                $sqlFormat = "DATE_FORMAT(al.create_date, '%Y')";
                break;
            default:
                $sqlFormat = "DATE_FORMAT(al.create_date, '%Y/%m/%d')";
                break;
        }

        $qb = $this->accessLogRepository->createQueryBuilder('al')
        ->where('al.create_date BETWEEN :start_date AND :end_date')
        ->andWhere('al.customer_id IS NULL')
        ->select("DATE_FORMAT(al.create_date, $sqlFormat) AS access_date, COUNT(al.id) AS access_count")
        ->setParameter('start_date', $startDate)
        ->setParameter('end_date', $endDate)
        ->groupBy('access_date')
        ->orderBy('access_date', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
