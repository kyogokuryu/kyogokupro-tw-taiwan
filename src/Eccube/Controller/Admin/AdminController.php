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

namespace Eccube\Controller\Admin;

use Carbon\Carbon;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Eccube\Controller\AbstractController;
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

class AdminController extends AbstractController
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
        $this->pluginApiService = $pluginApiService;
    }

    /**
     * @Route("/%eccube_admin_route%/login", name="admin_login")
     * @Template("@admin/login.twig")
     */
    public function login(Request $request)
    {
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_homepage');
        }

        /* @var $form \Symfony\Component\Form\FormInterface */
        $builder = $this->formFactory->createNamedBuilder('', LoginType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ADMIM_LOGIN_INITIALIZE, $event);

        $form = $builder->getForm();

        return [
            'error' => $this->helper->getLastAuthenticationError(),
            'form' => $form->createView(),
        ];
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

        // 今日の売上/件数
        $salesToday = $this->getSalesByDay(new \DateTime());
        // 昨日の売上/件数
        $salesYesterday = $this->getSalesByDay(new \DateTime('-1 day'));
        // 今月の売上/件数
        $salesThisMonth = $this->getSalesByMonth(new \DateTime());

        /**
         * ショップ状況
         */
        // 在庫切れ商品数
        $countNonStockProducts = $this->countNonStockProducts();

        // 取り扱い商品数
        $countProducts = $this->countProducts();

        // 本会員数
        $countCustomers = $this->countCustomers();

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
        ];
    }

    /**
     * 売上状況の取得
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/sale_chart", name="admin_homepage_sale")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function sale(Request $request)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
        //    return $this->json(['status' => 'NG'], 400);
        }
        $elm = $this;
        //return $this->cache('AdminController.sale', function() use($elm){
        // 週間の売上金額
        $toDate = Carbon::now();
        $fromDate = Carbon::today()->subWeek();
        $saleTypeWeekly = $elm->getSaleTypeData($fromDate, $toDate, 'Y/m/d');

        $toDate = Carbon::now();
        $fromDate = Carbon::today()->subWeek();
        $rawWeekly = $elm->getData($fromDate, $toDate, 'Y/m/d');

        // 月間の売上金額
        $toDate = Carbon::now();
        $fromDate = Carbon::now()->startOfMonth();
        $saleTypeMonthly = $elm->getSaleTypeData($fromDate, $toDate, 'Y/m/d');

        $toDate = Carbon::now();
        $fromDate = Carbon::now()->startOfMonth();
        $rawMonthly = $elm->getData($fromDate, $toDate, 'Y/m/d');

        // 年間の売上金額
        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subYear()->startOfMonth();
        $saleTypeYear = $elm->getSaleTypeData($fromDate, $toDate, 'Y/m');

        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subYear()->startOfMonth();
        $rawYear = $elm->getData($fromDate, $toDate, 'Y/m');

        // 年別の売上金額
        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subYear(10)->startOfYear(); 
        $saleTypeEvYear = $elm->getSaleTypeData($fromDate, $toDate, 'Y');

        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subYear(10)->startOfYear(); 
        $rawEvYear = $elm->getData($fromDate, $toDate, 'Y');
        
        $saleTypeWeekly2["売上金額"] = $rawWeekly;
        foreach($saleTypeWeekly as $k=>$v){
            $saleTypeWeekly2[$k] = $v;
        }

        $saleTypeMonthly2["売上金額"] = $rawMonthly;
        foreach($saleTypeMonthly as $k=>$v){
            $saleTypeMonthly2[$k] = $v;
        }


        $saleTypeYear2["売上金額"] = $rawYear;
        foreach($saleTypeYear as $k=>$v){
            $saleTypeYear2[$k] = $v;
        }

        $saleTypeEvYear2["売上金額"] = $rawEvYear;
        foreach($saleTypeEvYear as $k=>$v){
            $saleTypeEvYear2[$k] = $v;
        } 

        $datas = [$saleTypeWeekly2, $saleTypeMonthly2, $saleTypeYear2, $saleTypeEvYear2];
        
        $response = [$datas];
        return $elm->json($response);
        //}, 60*10);
    }

    /**
     * アマゾン、楽天、qoo10用売上
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/sale_chart_ec_mall", name="admin_homepage_sale_ec_mall")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saleForEcMall(Request $request)
    {
        $elm = $this;
        $response = $this->cache('AdminController.saleForEcMall', function() use($elm){ 
            //ECサイト以外でまとめる
            // 年間の売上金額
            $toDate = Carbon::now();
            $fromDate = Carbon::now()->subYear()->startOfMonth();
            $saleTypeYearForNonEc = $elm->getSaleTypeData($fromDate, $toDate, 'Y/m', false);

            $toDate = Carbon::now();
            $fromDate = Carbon::now()->subYear()->startOfMonth();
            $rawYearForNonEc = $elm->getData($fromDate, $toDate, 'Y/m', false);

            // 年別の売上金額
            $toDate = Carbon::now();
            $fromDate = Carbon::now()->subYear(10)->startOfYear(); 
            $saleTypeEvYearForNonEc = $elm->getSaleTypeData($fromDate, $toDate, 'Y', false);

            $toDate = Carbon::now();
            $fromDate = Carbon::now()->subYear(10)->startOfYear(); 
            $rawEvYearForNonEc = $elm->getData($fromDate, $toDate, 'Y', false);

            $saleTypeYearForNonEc2["売上金額"] = $rawYearForNonEc;
            foreach($saleTypeYearForNonEc as $k=>$v){
                $saleTypeYearForNonEc2[$k] = $v;
            }
            
            $saleTypeEvYearForNonEc2["売上金額"] = $rawEvYearForNonEc;
            foreach($saleTypeEvYearForNonEc as $k=>$v){
                $saleTypeEvYearForNonEc2[$k] = $v;
            } 
            $datas = [$saleTypeYearForNonEc2, $saleTypeEvYearForNonEc2];
            $response = [$datas];
            
            return $response;
        }, 60*60*24);

        return $elm->json($response);
    }

     /**
     * ライブコマースの売上状況一覧
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/sale_chart_live_commerce", name="admin_homepage_sale_live_commerce")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saleForLiveCommerce(Request $request)
    {
        $elm = $this;
         //ライブコマース売上を算出する
        // 週間の売上金額
        $toDate = Carbon::now();
        $fromDate = Carbon::today()->subWeek();
        $saleTypeWeeklyForLiveCommerce = $elm->getSaleTypeDataForLiveCommerce($fromDate, $toDate, 'Y/m/d');

        $toDate = Carbon::now();
        $fromDate = Carbon::today()->subWeek();
        $rawWeeklyForLiveCommerce = $elm->getDataForLiveCommerce($fromDate, $toDate, 'Y/m/d');

        // 月間の売上金額
        $toDate = Carbon::now();
        $fromDate = Carbon::now()->startOfMonth();
        $saleTypeMonthlyForLiveCommerce = $elm->getSaleTypeDataForLiveCommerce($fromDate, $toDate, 'Y/m/d');

        $toDate = Carbon::now();
        $fromDate = Carbon::now()->startOfMonth();
        $rawMonthlyForLiveCommerce = $elm->getDataForLiveCommerce($fromDate, $toDate, 'Y/m/d');

        // 年間の売上金額
        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subYear()->startOfMonth();
        $saleTypeYearForLiveCommerce = $elm->getSaleTypeDataForLiveCommerce($fromDate, $toDate, 'Y/m');

        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subYear()->startOfMonth();
        $rawYearForLiveCommerce = $elm->getDataForLiveCommerce($fromDate, $toDate, 'Y/m');

        // 年別の売上金額
        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subYear(10)->startOfYear(); 
        $saleTypeEvYearForLiveCommerce = $elm->getSaleTypeDataForLiveCommerce($fromDate, $toDate, 'Y');

        $toDate = Carbon::now();
        $fromDate = Carbon::now()->subYear(10)->startOfYear(); 
        $rawEvYearForLiveCommerce = $elm->getDataForLiveCommerce($fromDate, $toDate, 'Y');
        
        $saleTypeWeeklyForLiveCommerce2["売上金額"] = $rawWeeklyForLiveCommerce;
        foreach($saleTypeWeeklyForLiveCommerce as $k=>$v){
            $saleTypeWeeklyForLiveCommerce2[$k] = $v;
        }

        $saleTypeMonthlyForLiveCommerce2["売上金額"] = $rawMonthlyForLiveCommerce;
        foreach($saleTypeMonthlyForLiveCommerce as $k=>$v){
            $saleTypeMonthlyForLiveCommerce2[$k] = $v;
        }

        $saleTypeYearForLiveCommerce2["売上金額"] = $rawYearForLiveCommerce;
        foreach($saleTypeYearForLiveCommerce as $k=>$v){
            $saleTypeYearForLiveCommerce2[$k] = $v;
        }
        
        $saleTypeEvYearForLiveCommerce2["売上金額"] = $rawEvYearForLiveCommerce;
        foreach($saleTypeEvYearForLiveCommerce as $k=>$v){
            $saleTypeEvYearForLiveCommerce2[$k] = $v;
        } 
        $datas = [$saleTypeWeeklyForLiveCommerce2, $saleTypeMonthlyForLiveCommerce2, $saleTypeYearForLiveCommerce2, $saleTypeEvYearForLiveCommerce2];
        $response = [$datas];
        return $elm->json($response);
    }

    /**
     * ライブコマースの売上状況詳細
     *
     * @param Request $request
     *
     * @Route("/%eccube_admin_route%/sale_detail_chart_live_commerce", name="admin_homepage_sale_detail_live_commerce")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saleDetailForLiveCommerce(Request $request)
    {   
        $dateStr = $request->get('date');
        $dateType = $request->get('dayType');
        
        $date;
        $startDatetime;
        $endDatetime;

        //年間
        if($dateType == 'Y/m'){
            $date = Carbon::createFromFormat('Y/m', $dateStr)->startOfMonth();
            $startDatetime = $date->copy();
            $endDatetime = $date->copy()->endOfMonth();
        //週間、月間
        }else if($dateType == 'Y/m/d'){
            $date = Carbon::createFromFormat('Y/m/d', $dateStr);
            $startDatetime = $date->copy();
            $endDatetime = $date->copy()->addDay();
        }else{
            $date = Carbon::createFromFormat('Y', $dateStr);
            $startDatetime = $date->copy()->startOfYear();
            $endDatetime = $date->copy()->endOfYear();
        }

        $qb = $this->orderRepository->createQueryBuilder('o')
        ->join('o.OrderItems', 'oi')
        ->andWhere('oi.product_name like :liveCommerceKeyword OR oi.product_name like :liveCommerceKeyword2')
        ->andWhere('o.order_date >= :fromDate')
        ->andWhere('o.order_date < :toDate')
        ->setParameter(':liveCommerceKeyword', "%専用%")
        ->setParameter(':liveCommerceKeyword2', "%TikTok%")
        ->setParameter(':fromDate', $startDatetime->format('Y/m/d'))
        ->setParameter(':toDate', $endDatetime->format('Y/m/d'))
        ->orderBy('o.order_date', 'DESC');

        $results = [];
        
        foreach($qb->getQuery()->getResult() as $v){
            $OrderItems = $v->getOrderItems();
            $items = [];
            foreach ($OrderItems as $OrderItem) {
                $name = $OrderItem->getProductName();
                $price = $OrderItem->getPrice();
                array_push($items, ["name" => $name, "price" => $price]);
            }
            array_push($results, [
                "order_items" => $items,
                "order_date" => $v["order_date"]->format('Y/m/d G:i'),
                "order_id" => $v["order_no"],
                "payment_total" => $v["payment_total"],
                "name01" => $v["name01"],
                "name02" => $v["name02"]
            ]);
        };
        return $this->json(['data' => $results]);
    }

    /**
     * パスワード変更画面
     *
     * @Route("/%eccube_admin_route%/change_password", name="admin_change_password")
     * @Template("@admin/change_password.twig")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
     */
    public function changePassword(Request $request)
    {
        $builder = $this->formFactory
            ->createBuilder(ChangePasswordType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ADMIM_CHANGE_PASSWORD_INITIALIZE, $event);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Member = $this->getUser();
            $salt = $Member->getSalt();
            $password = $form->get('change_password')->getData();

            $encoder = $this->encoderFactory->getEncoder($Member);

            // 2系からのデータ移行でsaltがセットされていない場合はsaltを生成.
            if (empty($salt)) {
                $salt = $encoder->createSalt();
            }

            $password = $encoder->encodePassword($password, $salt);

            $Member
                ->setPassword($password)
                ->setSalt($salt);

            $this->memberRepository->save($Member);

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Member' => $Member,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ADMIN_CHANGE_PASSWORD_COMPLETE, $event);

            $this->addSuccess('admin.change_password.password_changed', 'admin');

            return $this->redirectToRoute('admin_change_password');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * 在庫なし商品の検索結果を表示する.
     *
     * @Route("/%eccube_admin_route%/search_nonstock", name="admin_homepage_nonstock")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchNonStockProducts(Request $request)
    {
        // 在庫なし商品の検索条件をセッションに付与し, 商品マスタへリダイレクトする.
        $searchData = [];
        $searchData['stock'] = [ProductStock::OUT_OF_STOCK];
        $session = $request->getSession();
        $session->set('eccube.admin.product.search', $searchData);

        return $this->redirectToRoute('admin_product_page', [
            'page_no' => 1,
        ]);
    }

    /**
     * 本会員の検索結果を表示する.
     *
     * @Route("/%eccube_admin_route%/search_customer", name="admin_homepage_customer")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchCustomer(Request $request)
    {
        $searchData = [];
        $searchData['customer_status'] = [CustomerStatus::REGULAR];
        $session = $request->getSession();
        $session->set('eccube.admin.customer.search', $searchData);

        return $this->redirectToRoute('admin_customer_page', [
            'page_no' => 1,
        ]);
    }

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param array $excludes
     *
     * @return null|Request
     */
    protected function getOrderEachStatus(array $excludes)
    {
        $sql = 'SELECT
                    t1.order_status_id as status,
                    COUNT(t1.id) as count
                FROM
                    dtb_order t1
                WHERE
                    t1.order_status_id NOT IN (:excludes)
                GROUP BY
                    t1.order_status_id
                ORDER BY
                    t1.order_status_id';
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('status', 'status');
        $rsm->addScalarResult('count', 'count');
        $query = $this->entityManager->createNativeQuery($sql, $rsm);
        $query->setParameters([':excludes' => $excludes]);
        $result = $query->getResult();
        $orderArray = [];
        foreach ($result as $row) {
            $orderArray[$row['status']] = $row['count'];
        }

        return $orderArray;
    }

    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getSalesByDay($dateTime)
    {
        // concat... for pgsql
        // http://stackoverflow.com/questions/1091924/substr-does-not-work-with-datatype-timestamp-in-postgres-8-3
        $dql = 'SELECT
                  SUBSTRING(CONCAT(o.order_date, \'\'), 1, 10) AS order_day,
                  SUM(o.payment_total) AS order_amount,
                  COUNT(o) AS order_count
                FROM
                  Eccube\Entity\Order o
                WHERE
                    o.OrderStatus NOT IN (:excludes)
                    AND SUBSTRING(CONCAT(o.order_date, \'\'), 1, 10) = SUBSTRING(:targetDate, 1, 10)
                    AND o.payment_method not like :amazon AND o.payment_method not like :rakuten AND o.payment_method not like :qoo
                GROUP BY
                  order_day';

        $q = $this->entityManager
            ->createQuery($dql)
            ->setParameter(':excludes', $this->excludes)
            ->setParameter(':targetDate', $dateTime)
            ->setParameter(':amazon', "%アマゾン%")
            ->setParameter(':rakuten', "%楽天%")
            ->setParameter(':qoo', "%qoo%");

        $result = [];
        try {
            $result = $q->getSingleResult();
        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return $result;
    }

    /**
     * @param $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getSalesByMonth($dateTime)
    {
        // concat... for pgsql
        // http://stackoverflow.com/questions/1091924/substr-does-not-work-with-datatype-timestamp-in-postgres-8-3
        $dql = 'SELECT
                  SUBSTRING(CONCAT(o.order_date, \'\'), 1, 7) AS order_month,
                  SUM(o.payment_total) AS order_amount,
                  COUNT(o) AS order_count
                FROM
                  Eccube\Entity\Order o
                WHERE
                    o.OrderStatus NOT IN (:excludes)
                    AND SUBSTRING(CONCAT(o.order_date, \'\'), 1, 7) = SUBSTRING(:targetDate, 1, 7)
                    AND o.payment_method not like :amazon AND o.payment_method not like :rakuten AND o.payment_method not like :qoo
                GROUP BY
                  order_month';

        $q = $this->entityManager
            ->createQuery($dql)
            ->setParameter(':excludes', $this->excludes)
            ->setParameter(':targetDate', $dateTime)
            ->setParameter(':amazon', "%アマゾン%")
            ->setParameter(':rakuten', "%楽天%")
            ->setParameter(':qoo', "%qoo%");

        $result = [];
        try {
            $result = $q->getSingleResult();
        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return $result;
    }

    /**
     * 在庫切れ商品数を取得
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function countNonStockProducts()
    {
        $qb = $this->productRepository->createQueryBuilder('p')
            ->select('count(DISTINCT p.id)')
            ->innerJoin('p.ProductClasses', 'pc')
            ->where('pc.stock_unlimited = :StockUnlimited AND pc.stock = 0')
            ->setParameter('StockUnlimited', false);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 商品数を取得
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function countProducts()
    {
        $qb = $this->productRepository->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.Status in (:Status)')
            ->setParameter('Status', [ProductStatus::DISPLAY_SHOW, ProductStatus::DISPLAY_HIDE]);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 本会員数を取得
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function countCustomers()
    {
        $qb = $this->customerRepository->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.Status = :Status')
            ->setParameter('Status', CustomerStatus::REGULAR);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 期間指定のデータを取得
     *
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param $format
     *
     * @return array
     */
    protected function getData(Carbon $fromDate, Carbon $toDate, $format, $isEc = true, $isAllSale=false)
    {
        if($format == "Y/m/d"){
            $result = [];
            for ($date = $fromDate; $date <= $toDate; $date = $date->addDay()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y/m/d');
                $date2 = $date1->copy()->addDay();
                $qb = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->select("sum(o.payment_total) as price")
                ->setParameter(':fromDate', $date1->format('Y/m/d'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                if($isEc){
                    $qb->andWhere('o.payment_method not like :amazon')
                    ->andWhere('o.payment_method not like :rakuten')
                    ->andWhere('o.payment_method not like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }else if(!$isAllSale){
                    $qb->andWhere('o.payment_method like :amazon or o.payment_method like :rakuten or o.payment_method like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }

                $tmp = $qb->getQuery()->getSingleResult(); 
                $result[$ym] = ["price"=>$tmp["price"] ? floor($tmp["price"]) : 0 ];
            }

        }elseif($format == "Y/m"){


            $result = [];
            for ($date = $fromDate; $date <= $toDate; $date = $date->addMonth()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y/m');
                $date2 = $date1->copy()->addMonth();
                $qb = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->select("sum(o.payment_total) as price, ". $ym . " as order_date")
                ->setParameter(':fromDate', $date1->format('Y/m/01'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                if($isEc){
                    $qb->andWhere('o.payment_method not like :amazon')
                    ->andWhere('o.payment_method not like :rakuten')
                    ->andWhere('o.payment_method not like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }else if(!$isAllSale){
                    $qb->andWhere('o.payment_method like :amazon or o.payment_method like :rakuten or o.payment_method like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }

                $tmp = $qb->getQuery()->getSingleResult(); 
                $result[$ym] = ["price"=>$tmp["price"] ? floor($tmp["price"]) : 0 ];
            }
        
        }elseif($format == "Y"){

            ;
            $result = [];
            for ($date = $fromDate; $date <= $toDate; $date = $date->addYear()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y');
                $date2 = $date1->copy()->addYear();
                $qb = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->select("sum(o.payment_total) as price, ". $ym . " as order_date")
                ->setParameter(':fromDate', $date1->format('Y/01/01'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                if($isEc){
                    $qb->andWhere('o.payment_method not like :amazon')
                    ->andWhere('o.payment_method not like :rakuten')
                    ->andWhere('o.payment_method not like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }else if(!$isAllSale){
                    $qb->andWhere('o.payment_method like :amazon or o.payment_method like :rakuten or o.payment_method like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }

                $tmp = $qb->getQuery()->getSingleResult(); 
                $result[$ym] = ["price"=>$tmp["price"] ? floor($tmp["price"]) : 0 ];
            }
        }


        return $result; //$this->convert($result, $fromDate, $toDate, $format);
    }

    /**
     * 期間指定のデータを取得 ライブコマース用
     *
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param $format
     *
     * @return array
     */
    protected function getDataForLiveCommerce(Carbon $fromDate, Carbon $toDate, $format)
    {
        if($format == "Y/m/d"){
            $result = [];
            for ($date = $fromDate; $date <= $toDate; $date = $date->addDay()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y/m/d');
                $date2 = $date1->copy()->addDay();
                $qb = $this->orderRepository->createQueryBuilder('o')
                ->join('o.OrderItems', 'oi')
                ->andWhere('oi.product_name like :liveCommerceKeyword OR oi.product_name like :liveCommerceKeyword2')
                ->setParameter(':liveCommerceKeyword', "%専用%")
                ->setParameter(':liveCommerceKeyword2', "%TikTok%")
                ->andWhere('o.order_date IS NOT NULL')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->select("sum(o.payment_total) as price")
                ->setParameter(':fromDate', $date1->format('Y/m/d'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                $tmp = $qb->getQuery()->getSingleResult(); 
                $result[$ym] = ["price"=>$tmp["price"] ? floor($tmp["price"]) : 0 ];
            }

        }elseif($format == "Y/m"){


            $result = [];
            for ($date = $fromDate; $date <= $toDate; $date = $date->addMonth()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y/m');
                $date2 = $date1->copy()->addMonth();
                $qb = $this->orderRepository->createQueryBuilder('o')
                ->join('o.OrderItems', 'oi')
                ->andWhere('oi.product_name like :liveCommerceKeyword OR oi.product_name like :liveCommerceKeyword2')
                ->setParameter(':liveCommerceKeyword', "%専用%")
                ->setParameter(':liveCommerceKeyword2', "%TikTok%")
                ->andWhere('o.order_date IS NOT NULL')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->select("sum(o.payment_total) as price, ". $ym . " as order_date")
                ->setParameter(':fromDate', $date1->format('Y/m/01'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));
                $tmp = $qb->getQuery()->getSingleResult(); 
                $result[$ym] = ["price"=>$tmp["price"] ? floor($tmp["price"]) : 0 ];
            }
        
        }elseif($format == "Y"){

            ;
            $result = [];
            for ($date = $fromDate; $date <= $toDate; $date = $date->addYear()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y');
                $date2 = $date1->copy()->addYear();
                $qb = $this->orderRepository->createQueryBuilder('o')
                ->join('o.OrderItems', 'oi')
                ->andWhere('oi.product_name like :liveCommerceKeyword OR oi.product_name like :liveCommerceKeyword2')
                ->setParameter(':liveCommerceKeyword', "%専用%")
                ->setParameter(':liveCommerceKeyword2', "%TikTok%")
                ->andWhere('o.order_date IS NOT NULL')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                //->select("sum(o.payment_total) as price, ". $ym . " as order_date")
                ->select("sum(ABS(oi.price)) as price, ". $ym . " as order_date")
                ->setParameter(':fromDate', $date1->format('Y/01/01'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                $tmp = $qb->getQuery()->getSingleResult(); 
                $result[$ym] = ["price"=>$tmp["price"] ? floor($tmp["price"]) : 0 ];
            }
        }


        return $result; //$this->convert($result, $fromDate, $toDate, $format);
    }
    /**
     * 期間毎にデータをまとめる
     *
     * @param $result
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param $format
     *
     * @return array
     */
    protected function convert($result, Carbon $fromDate, Carbon $toDate, $format)
    {
        $raw = [];
        if($format == "Y/m/d"){
            for ($date = $fromDate; $date <= $toDate; $date = $date->addDay()) {
                $raw[$date->format($format)]['price'] = 0;
                $raw[$date->format($format)]['count'] = 0;
            }

            foreach ($result as $Order) {

                $raw[$Order["order_date"]->format($format)]['price'] += $Order["price"];
                ++$raw[$Order["order_date"]->format($format)]['count'];
            }
        }elseif($format == "Y/m"){
            for ($date = $fromDate; $date <= $toDate; $date = $date->addMonth()) {
                $raw[$date->format($format)]['price'] = 0;
                $raw[$date->format($format)]['count'] = 0;
            }

            foreach ($result as $Order) {
                $raw[$Order["order_date"]->format($format)]['price'] += $Order["price"];
                ++$raw[$Order["order_date"]->format($format)]['count'];
            }
        
        }elseif($format == "Y"){
            for ($date = $fromDate; $date <= $toDate; $date = $date->addYear()) {
                $raw[$date->format($format)]['price'] = 0;
                $raw[$date->format($format)]['count'] = 0;
            }

            foreach ($result as $Order) {
                $raw[$Order["order_date"]->format($format)]['price'] += $Order["price"];
                ++$raw[$Order["order_date"]->format($format)]['count'];
            }
        
        }
        return $raw;
    }

    /**
     * 期間指定のデータを取得
     *
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param $format
     *
     * @return array
     */
    protected function getSaleTypeData(Carbon $fromDate, Carbon $toDate, $format='Y/m/d', $isEc=true)
    {
        if($format == "Y/m/d"){

            $result = [];
            $initFromDate = $fromDate->copy();
            for ($date = $initFromDate; $date <= $toDate; $date = $date->addDay()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y/m/d');
                $date2 = $date1->copy()->addDay();

                $qb = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->setParameter(':fromDate', $date1->format('Y/m/d'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                if($isEc){
                    $qb->andWhere('o.payment_method not like :amazon')
                    ->andWhere('o.payment_method not like :rakuten')
                    ->andWhere('o.payment_method not like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }else{
                    $qb->andWhere('o.payment_method like :amazon or o.payment_method like :rakuten or o.payment_method like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }
                $qb->groupBy('o.payment_method')
                ->select('
                    o.payment_method,
                    SUM(o.payment_total) AS order_amount,
                    COUNT(o) AS order_count
                ');

                foreach($qb->getQuery()->getResult() as $v){
                    if(!isset($result[$v["payment_method"]])){ 
                        $initTmpFromDate = $fromDate->copy();
                        $result[$v["payment_method"]] = []; 
                        for ($init_date = $initTmpFromDate; $init_date <= $toDate; $init_date = $init_date->addDay()) {
                            $init_date1 = $init_date->copy();
                            $ymd = $init_date1->format('Y/m/d');
                            $result[$v["payment_method"]][$ymd] = ["price"=>0];
                        }
                    }
                    $result[$v["payment_method"]][$ym] = ["price"=>$v["order_amount"] ? floor($v["order_amount"]) : 0]; 
                }

            }

        }elseif($format == "Y/m"){

            $result = [];

            $initFromDate = $fromDate->copy();
            for ($date = $initFromDate; $date <= $toDate; $date = $date->addMonth()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y/m');
                $date2 = $date1->copy()->addMonth();

                $qb = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->setParameter(':fromDate', $date1->format('Y/m/01'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                if($isEc){
                    $qb->andWhere('o.payment_method not like :amazon')
                    ->andWhere('o.payment_method not like :rakuten')
                    ->andWhere('o.payment_method not like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }else{
                    $qb->andWhere('o.payment_method like :amazon or o.payment_method like :rakuten or o.payment_method like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }

                $qb->groupBy('o.payment_method')
                ->select('
                    o.payment_method,
                    SUM(o.payment_total) AS order_amount,
                    COUNT(o) AS order_count
                ');

                foreach($qb->getQuery()->getResult() as $v){
                    if(!isset($result[$v["payment_method"]])){ 
                        $result[$v["payment_method"]] = []; 
                        $initTmpFromDate = $fromDate->copy();
                        for ($init_date = $initTmpFromDate; $init_date <= $toDate; $init_date = $init_date->addMonth()) {
                            $ymd = $init_date->format('Y/m');
                            $result[$v["payment_method"]][$ymd] = ["price"=>0];
                        }
                    }
                    $result[$v["payment_method"]][$ym] = ["price"=>$v["order_amount"] ? floor($v["order_amount"]) : 0 ,'order_date'=>$ym]; 
                }
            }
        
        }elseif($format == "Y"){
            $result = [];

            $initFromDate = $fromDate->copy();
            for ($date = $initFromDate; $date <= $toDate; $date = $date->addYear()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y');
                $date2 = $date1->copy()->addYear();

                $qb = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->setParameter(':fromDate', $date1->format('Y/01/01'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                if($isEc){
                    $qb->andWhere('o.payment_method not like :amazon')
                    ->andWhere('o.payment_method not like :rakuten')
                    ->andWhere('o.payment_method not like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }else{
                    $qb->andWhere('o.payment_method like :amazon or o.payment_method like :rakuten or o.payment_method like :qoo')
                    ->setParameter(':amazon', "%アマゾン%")
                    ->setParameter(':rakuten', "%楽天%")
                    ->setParameter(':qoo', "%qoo%");
                }

                $qb->groupBy('o.payment_method')
                ->select('
                    o.payment_method,
                    SUM(o.payment_total) AS order_amount,
                    COUNT(o) AS order_count
                ');

                foreach($qb->getQuery()->getResult() as $v){
                    if(!isset($result[$v["payment_method"]])){ 
                        $result[$v["payment_method"]] = []; 
                        $initTmpFromDate = $fromDate->copy();
                        for ($init_date = $initTmpFromDate; $init_date <= $toDate; $init_date = $init_date->addYear()) {
                            $ymd = $init_date->format('Y');
                            $result[$v["payment_method"]][$ymd] = ["price"=>0];
                        }
                    }
                    $result[$v["payment_method"]][$ym] = ["price"=>$v["order_amount"] ? floor($v["order_amount"]) : 0 ,'order_date'=>$ym]; 
                }
            }
        
        }
        

        foreach($result as $k=>$list){
            ksort($list);
            $result[$k] = $list;
        }
        ksort($result);

        return $result;
    }

    /**
     * 期間指定のデータを取得 ライブコマース用
     *
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param $format
     *
     * @return array
     */
    protected function getSaleTypeDataForLiveCommerce(Carbon $fromDate, Carbon $toDate, $format='Y/m/d')
    {
        if($format == "Y/m/d"){

            $result = [];
            $initFromDate = $fromDate->copy();
            for ($date = $initFromDate; $date <= $toDate; $date = $date->addDay()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y/m/d');
                $date2 = $date1->copy()->addDay();

                $qb = $this->orderRepository->createQueryBuilder('o')
                ->join('o.OrderItems', 'oi')
                ->andWhere('oi.product_name like :liveCommerceKeyword OR oi.product_name like :liveCommerceKeyword2')
                ->setParameter(':liveCommerceKeyword', "%専用%")
                ->setParameter(':liveCommerceKeyword2', "%TikTok%")
                ->andWhere('o.order_date IS NOT NULL')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->setParameter(':fromDate', $date1->format('Y/m/d'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                $qb->groupBy('o.payment_method')
                ->select('
                    o.payment_method,
                    SUM(o.payment_total) AS order_amount,
                    COUNT(o) AS order_count
                ');

                foreach($qb->getQuery()->getResult() as $v){
                    if(!isset($result[$v["payment_method"]])){ 
                        $initTmpFromDate = $fromDate->copy();
                        $result[$v["payment_method"]] = []; 
                        for ($init_date = $initTmpFromDate; $init_date <= $toDate; $init_date = $init_date->addDay()) {
                            $init_date1 = $init_date->copy();
                            $ymd = $init_date1->format('Y/m/d');
                            $result[$v["payment_method"]][$ymd] = ["price"=>0];
                        }
                    }
                    $result[$v["payment_method"]][$ym] = ["price"=>$v["order_amount"] ? floor($v["order_amount"]) : 0]; 
                }

            }

        }elseif($format == "Y/m"){

            $result = [];

            $initFromDate = $fromDate->copy();
            for ($date = $initFromDate; $date <= $toDate; $date = $date->addMonth()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y/m');
                $date2 = $date1->copy()->addMonth();

                $qb = $this->orderRepository->createQueryBuilder('o')
                ->join('o.OrderItems', 'oi')
                ->andWhere('oi.product_name like :liveCommerceKeyword')
                ->setParameter(':liveCommerceKeyword', "%専用%")
                ->andWhere('o.order_date IS NOT NULL')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->setParameter(':fromDate', $date1->format('Y/m/01'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                $qb->groupBy('o.payment_method')
                ->select('
                    o.payment_method,
                    SUM(o.payment_total) AS order_amount,
                    COUNT(o) AS order_count
                ');

                foreach($qb->getQuery()->getResult() as $v){
                    if(!isset($result[$v["payment_method"]])){ 
                        $result[$v["payment_method"]] = []; 
                        $initTmpFromDate = $fromDate->copy();
                        for ($init_date = $initTmpFromDate; $init_date <= $toDate; $init_date = $init_date->addMonth()) {
                            $ymd = $init_date->format('Y/m');
                            $result[$v["payment_method"]][$ymd] = ["price"=>0];
                        }
                    }
                    $result[$v["payment_method"]][$ym] = ["price"=>$v["order_amount"] ? floor($v["order_amount"]) : 0 ,'order_date'=>$ym]; 
                }
            }
        
        }elseif($format == "Y"){
            $result = [];

            $initFromDate = $fromDate->copy();
            for ($date = $initFromDate; $date <= $toDate; $date = $date->addYear()) {
                $date1 = $date->copy();
                $ym = $date1->format('Y');
                $date2 = $date1->copy()->addYear();

                $qb = $this->orderRepository->createQueryBuilder('o')
                ->join('o.OrderItems', 'oi')
                ->andWhere('oi.product_name like :liveCommerceKeyword OR oi.product_name like :liveCommerceKeyword2')
                ->setParameter(':liveCommerceKeyword', "%専用%")
                ->setParameter(':liveCommerceKeyword2', "%TikTok%")
                ->andWhere('o.order_date IS NOT NULL')
                ->andWhere('o.order_date >= :fromDate')
                ->andWhere('o.order_date < :toDate')
                ->andWhere('o.OrderStatus NOT IN (:excludes)')
                ->setParameter(':excludes', $this->excludes)
                ->setParameter(':fromDate', $date1->format('Y/01/01'))
                ->setParameter(':toDate', $date2->format('Y/m/d'));

                $qb->groupBy('o.payment_method')
                ->select('
                    o.payment_method,
                    SUM(o.payment_total) AS order_amount,
                    COUNT(o) AS order_count
                ');

                foreach($qb->getQuery()->getResult() as $v){
                    if(!isset($result[$v["payment_method"]])){ 
                        $result[$v["payment_method"]] = []; 
                        $initTmpFromDate = $fromDate->copy();
                        for ($init_date = $initTmpFromDate; $init_date <= $toDate; $init_date = $init_date->addYear()) {
                            $ymd = $init_date->format('Y');
                            $result[$v["payment_method"]][$ymd] = ["price"=>0];
                        }
                    }
                    $result[$v["payment_method"]][$ym] = ["price"=>$v["order_amount"] ? floor($v["order_amount"]) : 0 ,'order_date'=>$ym]; 
                }
            }
        
        }
        

        foreach($result as $k=>$list){
            ksort($list);
            $result[$k] = $list;
        }
        ksort($result);

        return $result;
    }

    /**
     *
     *
     */
    protected function cache($name, $callback, $expire=3600){
        $dir = realpath(__DIR__ . "/../../../../var/cache/prod/");
        $cache_file = $dir ."/" . $name . ".cache";
        if(file_exists($cache_file)){
            if(mktime() + $expire > time()){
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
}
