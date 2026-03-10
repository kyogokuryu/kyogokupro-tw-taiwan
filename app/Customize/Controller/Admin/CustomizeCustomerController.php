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

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CsvType;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Customize\Form\SearchCustomerType;
use Customize\Form\Type\SearchClogType;
use Customize\Form\Type\SearchMlogType;
use Customize\Form\Type\SearchLivelogType;
use Customize\Repository\CustomizeCustomerRepository as CustomerRepository;
use Customize\Repository\CLogRepository;
use Customize\Repository\MLogRepository;
use Customize\Repository\LiveLogRepository;
//use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\Master\SexRepository;
use Eccube\Service\CsvExportService;
use Eccube\Service\MailService;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Customize\Entity\CLog;
use Customize\Entity\MLog;
use Customize\Entity\LiveLog;

use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\BaseInfo;
use Eccube\Repository\BaseInfoRepository;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class CustomizeCustomerController extends AbstractController
{
    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var PrefRepository
     */
    protected $prefRepository;

    /**
     * @var SexRepository
     */
    protected $sexRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     *  @var CLogRepository
     */
    protected $clogRepository;
    /**
     *  @var MLogRepository
     */
    protected $mlogRepository;
    /**
     *  @var LiveLogRepository
     */
    protected $livelogRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
    *
    */
    public function __construct(
        PageMaxRepository $pageMaxRepository,
        CustomerRepository $customerRepository,
        SexRepository $sexRepository,
        PrefRepository $prefRepository,
        MailService $mailService,
        CsvExportService $csvExportService,
        CLogRepository $clogRepository,
        MLogRepository $mlogRepository,
        LiveLogRepository $livelogRepository,
        BaseInfoRepository $baseInfoRepository
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->customerRepository = $customerRepository;
        $this->sexRepository = $sexRepository;
        $this->prefRepository = $prefRepository;
        $this->mailService = $mailService;
        $this->csvExportService = $csvExportService;
        $this->clogRepository = $clogRepository;
        $this->mlogRepository = $mlogRepository;
        $this->livelogRepository = $livelogRepository;
        $this->BaseInfo = $baseInfoRepository->get();
    }

    /**
     * @Route("/%eccube_admin_route%/customer", name="admin_customer")
     * @Route("/%eccube_admin_route%/customer/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_customer_page")
     * @Template("@admin/Customer/index.twig")
     */
    public function index(Request $request, $page_no = null, Paginator $paginator)
    {
        $session = $this->session;
        $builder = $this->formFactory->createBuilder(SearchCustomerType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();


        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('eccube.admin.customer.search.page_count', $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('eccube.admin.customer.search.page_count', $pageCount);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $page_no = 1;

                $session->set('eccube.admin.customer.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.customer.search.page_no', $page_no);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $session->set('eccube.admin.customer.search.page_no', (int) $page_no);
                } else {
                    $page_no = $session->get('eccube.admin.customer.search.page_no', 1);
                }
                $viewData = $session->get('eccube.admin.customer.search', []);
            } else {
                $page_no = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('eccube.admin.customer.search', $viewData);
                $session->set('eccube.admin.customer.search.page_no', $page_no);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }

        /** @var QueryBuilder $qb */
        $qb = $this->customerRepository->getQueryBuilderBySearchData($searchData);

        $event = new EventArgs(
            [
                'form' => $searchForm,
                'qb' => $qb,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount
        );


        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
        ];
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
        if($request->get('prime_member')){
            $searchData['prime_member'] = $request->get('prime_member');
        }
        $session = $request->getSession();
        $session->set('eccube.admin.customer.search', $searchData);

        return $this->redirectToRoute('admin_customer_page', [
            'page_no' => 1,
        ]);
    }

    /**
     *
     *  カスタマー履歴を保存
     *
     * @Route("/%eccube_admin_route%/clog/{id}/save", name="admin_homepage_clog_save")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveClog(Request $request, Customer $Customer=null){

        $c_staff = $request->get('c_staff');
        //$c_staff = "xxx";
        $c_date = $request->get('c_date');
        $today_msg = $request->get('today_msg');
        $next_msg = $request->get('next_msg');
        $c_needs = $request->get('c_needs');
        $memo = $request->get('memo');
        $id = $request->get('clog_id');
        $order_id = $request->get('order_id');
        $c_cate = $request->get('c_cate');
        $c_status = $request->get('c_status');
        $c_time = $request->get('c_time');

        $Clog = null;
        if($id){
            $Clog = $this->clogRepository->find($id);
        }

        if($Clog == null){
            $Clog = new CLog;
            $Clog->setCreateDate(new \DateTime());
        }

        $Clog->setC_staff($c_staff);
        $Clog->setC_date(new \DateTime($c_date));
        $Clog->setTodayMsg($today_msg);
        $Clog->setNextMsg($next_msg);
        $Clog->setC_needs($c_needs);
        $Clog->setMemo($memo);
        $Clog->setC_cate($c_cate);
        $Clog->setC_status($c_status);
        $Clog->setC_time($c_time);
        $Clog->setCustomer($Customer);
        $Clog->setUpdateDate(new \DateTime());

        if($order_id){
            $Order = $this->entityManager->getRepository('Eccube\Entity\Order')->find($order_id);
            $Clog->setOrder($Order);
        }else{
            $Clog->setOrder(null);
        }
        //log_error('Order', [$order_id]);



        $this->clogRepository->save($Clog);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_customer_edit', ['id'=>$Customer->getId(),'cedit'=>1]);
    }  

    /**
     * カスタマー履歴一覧.
     *
     * @Route("/%eccube_admin_route%/cloglist", name="admin_customer_cloglist")
     * @Route("/%eccube_admin_route%/cloglist/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_customer_cloglist_page")
     * @Template("@admin/Customer/Clog/index.twig")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchClog(Request $request, $page_no = null, Paginator $paginator)
    {
        $session = $this->session;
        $builder = $this->formFactory->createBuilder(SearchClogType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        $eccube_default_page_count = $this->eccubeConfig['eccube_default_page_count'];
        $eccube_default_page_count = 50;

        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('eccube.admin.clog.search.page_count', $eccube_default_page_count);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('eccube.admin.clog.search.page_count', $pageCount);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $page_no = 1;

                $session->set('eccube.admin.clog.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.clog.search.page_no', $page_no);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $session->set('eccube.admin.clog.search.page_no', (int) $page_no);
                } else {
                    $page_no = $session->get('eccube.admin.clog.search.page_no', 1);
                }
                $viewData = $session->get('eccube.admin.clog.search', []);
            } else {
                $page_no = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('eccube.admin.clog.search', $viewData);
                $session->set('eccube.admin.clog.search.page_no', $page_no);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }

        /** @var QueryBuilder $qb */
        $qb = $this->clogRepository->getQueryBuilderBySearchData($searchData);

        $event = new EventArgs(
            [
                'form' => $searchForm,
                'qb' => $qb,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount
        );

        $c_qb = $this->clogRepository->getCountPerStaff();
        $c_qb_comparison = $this->clogRepository->getCountPerStaffWithComparison();

        // データを担当者ごとに分割します
        $salesData = $this->clogRepository->getSalesPerStaffPastYear();

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
            'c_qb' => $c_qb,
            'c_qb_comparison' => $c_qb_comparison,
            'staffSalesData' => $salesData,
        ];
    }


    /**
     *
     *  メンバー報告履歴を保存
     *
     * @Route("/%eccube_admin_route%/Mlog/save", name="admin_homepage_mlog_save")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveMlog(Request $request, Customer $Customer=null){

        $c_staff = $request->get('c_staff');
        //$c_staff = "xxx";
        $c_date = $request->get('c_date');
        $today_msg = $request->get('today_msg');
        $next_msg = $request->get('next_msg');
        $memo = $request->get('memo');
        $comm = $request->get('comm');
        $id = $request->get('mlog_id');
        // $order_id = $request->get('order_id');
        $c_cate = $request->get('c_cate');
        $c_time = $request->get('c_time');
        $is_ceo = $request->get('is_ceo');

        $Mlog = null;
        if($id){
            $Mlog = $this->mlogRepository->find($id);
        }

        if($Mlog == null){
            $Mlog = new MLog;
            $Mlog->setCreateDate(new \DateTime());
        }

        $Mlog->setC_staff($c_staff);
        $Mlog->setC_date(new \DateTime($c_date));
        $Mlog->setTodayMsg($today_msg);
        $Mlog->setNextMsg($next_msg);
        $Mlog->setMemo($memo);
        $Mlog->setComm($comm);
        $Mlog->setC_cate($c_cate);
        $Mlog->setC_time($c_time);
        $Mlog->setIsCeo($is_ceo);
        $Mlog->setUpdateDate(new \DateTime());


        //log_error('Order', [$order_id]);

        $this->mlogRepository->save($Mlog);
        $this->entityManager->flush();

        if ($is_ceo == 0) {
            return $this->redirectToRoute('admin_staff_mloglist_page', ['c_date' => $c_date, 'c_staff' => $c_staff]);
        } else {
            return $this->redirectToRoute('admin_customer_mloglist_page', ['c_date' => $c_date, 'c_staff' => $c_staff]);
        }
    }

    /**
     *
     *  日報のコメント返信用
     *
     * @Route("/%eccube_admin_route%/Mlog/comment/save", name="admin_homepage_comment_save")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveComment(Request $request, Customer $Customer=null){
        $manager = $request->get('manager-edit');
        $comm = $request->get('comm-edit');
        $id = $request->get('mlog_id');

        $Mlog = $this->mlogRepository->find($id);
        $Mlog->setComm($comm);
        $Mlog->setCommManager($manager);
        $Mlog->setUpdateDate(new \DateTime());

        $this->mlogRepository->save($Mlog);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_customer_mloglist_page', ['c_date'=>$c_date, 'c_staff'=>$c_staff]);
    }


    /**
     * メンバー報告ー履歴一覧.
     *
     * @Route("/%eccube_admin_route%/mloglist", name="admin_customer_mloglist")
     * @Route("/%eccube_admin_route%/mloglist/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_customer_mloglist_page")
     * @Template("@admin/Customer/Mlog/index.twig")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchMlog(Request $request, $page_no = null, Paginator $paginator)
    {
        $is_logged_in = $this->session->get('LOGGIN_PRODUCT_COST') ? true : false;
        if ($is_logged_in == 1) {
            $logged_in = true;
        }else{
            $logged_in = false;
        }
        $session = $this->session;
        $builder = $this->formFactory->createBuilder(SearchMlogType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        $eccube_default_page_count = $this->eccubeConfig['eccube_default_page_count'];
        $eccube_default_page_count = 50;

        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('eccube.admin.Mlog.search.page_count', $eccube_default_page_count);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('eccube.admin.Mlog.search.page_count', $pageCount);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $page_no = 1;

                $session->set('eccube.admin.Mlog.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.Mlog.search.page_no', $page_no);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                    'logged_in' => $logged_in,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $session->set('eccube.admin.Mlog.search.page_no', (int) $page_no);
                } else {
                    $page_no = $session->get('eccube.admin.Mlog.search.page_no', 1);
                }
                $viewData = $session->get('eccube.admin.Mlog.search', []);
            } else {
                $page_no = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('eccube.admin.Mlog.search', $viewData);
                $session->set('eccube.admin.Mlog.search.page_no', $page_no);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }
        $is_ceo = 1;

        /** @var QueryBuilder $qb */
        $qb = $this->mlogRepository->getQueryBuilderBySearchData($searchData, $is_ceo);

        $event = new EventArgs(
            [
                'form' => $searchForm,
                'qb' => $qb,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount
        );

        $m_qb = $this->mlogRepository->getCountPerStaff();

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
            'm_qb' => $m_qb,
            'searchData' => $searchData,
            'logged_in' => $logged_in,
            'is_ceo' => $is_ceo,
        ];
    }


/**
     *
     *  メンバー報告履歴を保存
     *
     * @Route("/%eccube_admin_route%/Livelog/save", name="admin_homepage_livelog_save")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveLivelog(Request $request, Customer $Customer=null){

        $c_staff = $request->get('c_staff');
        //$c_staff = "xxx";
        $c_date = $request->get('c_date');
        $today_msg = $request->get('today_msg');
        $next_msg = $request->get('next_msg');
        $sell_memo = $request->get('sell_memo');
        $memo = $request->get('memo');
        $id = $request->get('livelog_id');
        // $order_id = $request->get('order_id');
        $c_cate = $request->get('c_cate');
        $c_time1 = $request->get('c_time1');
        $c_time2 = $request->get('c_time2');

        $c2 = strtotime($c_date ." " .$c_time2.":00");
        $c1 = strtotime($c_date ." " .$c_time1.":00");
        if($c2 < $c1){
            $c2 = strtotime($c_date ." " .$c_time2.":00 + 1 day");
        }
        $c_time = $c2 - $c1;//strtotime($c_date ." " .$c_time2.":00") - strtotime($c_date ." " .$c_time1.":00");
        

        $Livelog = null;
        if($id){
            $Livelog = $this->livelogRepository->find($id);
        }

        if($Livelog == null){
            $Livelog = new LiveLog;
            $Livelog->setCreateDate(new \DateTime());
        }

        $Livelog->setC_staff($c_staff);
        $Livelog->setC_date(new \DateTime($c_date));
        $Livelog->setTodayMsg($today_msg);
        $Livelog->setNextMsg($next_msg);
        $Livelog->setSellMemo($sell_memo);
        $Livelog->setMemo($memo);
        $Livelog->setC_cate($c_cate);
        $Livelog->setC_time($c_time);
        $Livelog->setC_time1($c_time1);
        $Livelog->setC_time2($c_time2);
        $Livelog->setUpdateDate(new \DateTime());


        //log_error('Order', [$order_id]);

        $this->livelogRepository->save($Livelog);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_customer_liveloglist_page', ['c_date'=>$c_date, 'c_staff'=>$c_staff]);
    }  

    /**
     * メンバー報告ー履歴一覧.
     *
     * @Route("/%eccube_admin_route%/liveloglist", name="admin_customer_liveloglist")
     * @Route("/%eccube_admin_route%/liveloglist/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_customer_liveloglist_page")
     * @Template("@admin/Customer/Livelog/index.twig")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchLivelog(Request $request, $page_no = null, Paginator $paginator)
    {
        $session = $this->session;
        $builder = $this->formFactory->createBuilder(SearchLivelogType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        $eccube_default_page_count = $this->eccubeConfig['eccube_default_page_count'];
        $eccube_default_page_count = 50;

        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('eccube.admin.Livelog.search.page_count', $eccube_default_page_count);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('eccube.admin.Livelog.search.page_count', $pageCount);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $page_no = 1;

                $session->set('eccube.admin.Livelog.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.Livelog.search.page_no', $page_no);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $session->set('eccube.admin.Livelog.search.page_no', (int) $page_no);
                } else {
                    $page_no = $session->get('eccube.admin.Livelog.search.page_no', 1);
                }
                $viewData = $session->get('eccube.admin.Livelog.search', []);
            } else {
                $page_no = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('eccube.admin.Livelog.search', $viewData);
                $session->set('eccube.admin.Livelog.search.page_no', $page_no);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }


        /** @var QueryBuilder $qb */
        $qb = $this->livelogRepository->getQueryBuilderBySearchData($searchData);

        $event = new EventArgs(
            [
                'form' => $searchForm,
                'qb' => $qb,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount
        );

        $m_qb = $this->livelogRepository->getCountPerStaff();



        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
            'm_qb' => $m_qb,
            'searchData' => $searchData,
        ];
    }

    /**
     * メンバー報告ー履歴一覧.
     *
     * @Route("/%eccube_admin_route%/staffmloglist", name="admin_staff_mloglist")
     * @Route("/%eccube_admin_route%/staffmloglist/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_staff_mloglist_page")
     * @Template("@admin/Customer/Mlog/staff_mlog_list.twig")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchMlogStaff(Request $request, $page_no = null, Paginator $paginator)
    {
        $is_logged_in = $this->session->get('LOGGIN_PRODUCT_COST') ? true : false;
        if ($is_logged_in == 1) {
            $logged_in = true;
        }else{
            $logged_in = false;
        }
        $session = $this->session;
        $builder = $this->formFactory->createBuilder(SearchMlogType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        $eccube_default_page_count = $this->eccubeConfig['eccube_default_page_count'];
        $eccube_default_page_count = 50;

        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('eccube.admin.Mlog.search.page_count', $eccube_default_page_count);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('eccube.admin.Mlog.search.page_count', $pageCount);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $page_no = 1;

                $session->set('eccube.admin.Mlog.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.Mlog.search.page_no', $page_no);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                    'logged_in' => $logged_in,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $session->set('eccube.admin.Mlog.search.page_no', (int) $page_no);
                } else {
                    $page_no = $session->get('eccube.admin.Mlog.search.page_no', 1);
                }
                $viewData = $session->get('eccube.admin.Mlog.search', []);
            } else {
                $page_no = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('eccube.admin.Mlog.search', $viewData);
                $session->set('eccube.admin.Mlog.search.page_no', $page_no);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }
        $is_ceo = 0;

        /** @var QueryBuilder $qb */
        $qb = $this->mlogRepository->getQueryBuilderBySearchData($searchData, $is_ceo);

        $event = new EventArgs(
            [
                'form' => $searchForm,
                'qb' => $qb,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount
        );

        $m_qb = $this->mlogRepository->getCountPerStaff();

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
            'm_qb' => $m_qb,
            'searchData' => $searchData,
            'logged_in' => $logged_in,
            'is_ceo' => $is_ceo,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/mlog/signin", name="admin_mlog_signin")
     */
    public function signinMlog(Request $request,EncoderFactoryInterface $encoderFactory)
    {
        $mlog_password = $this->BaseInfo->getMlogPassword();
        $password = $request->get('password');
        $customer = new Customer();
        $encoder = $encoderFactory->getEncoder($customer);
        $password = $encoder->encodePassword($password, null);
        if ($password == $mlog_password) {
            $this->session->set('LOGGIN_PRODUCT_COST', 1);
            return $this->json(['status' => 'success']);
        }else{
            return $this->json(['status' => 'failed']);
        }
    }

}
