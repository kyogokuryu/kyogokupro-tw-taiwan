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
use Customize\Repository\ShopEventPointLogRepository;
use Customize\Repository\ShopEventPointRepository;
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
use Customize\Entity\ShopEventPointLog;
use Customize\Entity\ShopEventPoint;


use Eccube\Entity\Master\CustomerStatus;

class CustomizeShopEventPointController extends AbstractController
{
    
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     *  @var ShopEventPointRepository
     */
    protected $shopEventPointRepository;
    /**
     *  @var ShopEventPointLogRepository
     */
    protected $shopEventPointLogRepository;

    /**
    *
    */
    public function __construct(
        PageMaxRepository $pageMaxRepository,
        CustomerRepository $customerRepository,
        ShopEventPointLogRepository $shopEventPointLogRepository,
        ShopEventPointRepository $shopEventPointRepository
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->customerRepository = $customerRepository;
        $this->shopEventPointRepository = $shopEventPointRepository;
        $this->shopEventPointLogRepository = $shopEventPointLogRepository;
    }


    /**
     *
     *  カスタマー履歴を保存
     *
     * @Route("/%eccube_admin_route%/shop_event_point/save", name="admin_homepage_shop_event_point_save")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveShopEventPoint(Request $request){

        $e_pass = $request->get('e_pass');
        //$c_staff = "xxx";
        $e_sdate = $request->get('e_sdate');
        $e_edate = $request->get('e_edate');
        
        $memo = $request->get('memo');
        $id = $request->get('shop_event_point_id');
/*
        $price1 = $request->get('price1');
        $price2 = $request->get('price2');
        $price3 = $request->get('price3');
        $price4 = $request->get('price4');
        $price5 = $request->get('price5');

        $value1 = $request->get('value1');
        $value2 = $request->get('value2');
        $value3 = $request->get('value3');
        $value4 = $request->get('value4');
        $value5 = $request->get('value5');

        $memo = [];
        if(is_numeric($price1) && is_numeric($value1)){ $memo[] = ["price"=>$price1, "value"=>$value1]; }
        if(is_numeric($price2) && is_numeric($value2)){ $memo[] = ["price"=>$price2, "value"=>$value2]; }
        if(is_numeric($price3) && is_numeric($value3)){ $memo[] = ["price"=>$price3, "value"=>$value3]; }
        if(is_numeric($price4) && is_numeric($value4)){ $memo[] = ["price"=>$price4, "value"=>$value4]; }
        if(is_numeric($price5) && is_numeric($value5)){ $memo[] = ["price"=>$price5, "value"=>$value5]; }

        $memo = json_encode($memo);
*/
        $ShopEventPoint = null;
        if($id){
            $ShopEventPoint = $this->shopEventPointRepository->find($id);
        }

        if($ShopEventPoint == null){
            $ShopEventPoint = new ShopEventPoint;
            $ShopEventPoint->setCreateDate(new \DateTime());
        }

        $ShopEventPoint->setE_pass($e_pass);
    //    $ShopEventPoint->setMemo($memo);
        $ShopEventPoint->setE_sdate(new \DateTime($e_sdate));
        $ShopEventPoint->setE_edate(new \DateTime($e_edate));
        $ShopEventPoint->setUpdateDate(new \DateTime());

        //log_error('Order', [$order_id]);



        $this->shopEventPointRepository->save($ShopEventPoint);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_customer_shop_event_point');
    }  

    /**
     *
     *  QR読取カメラ
     *
     * @Route("/%eccube_admin_route%/shop_event_point/camera", name="admin_homepage_shop_event_point_camera")
     * @Template("@admin/Customer/ShopEventPoint/camera.twig")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveShopEventPointCamera(Request $request){

        $id = 1;
        $hash_str = md5("id:".$id);

        return [
            "login"=>1,
            "hash_str"=>$hash_str,
        ];
    }
    /**
     *
     *  QR読取カメラ
     *
     * @Route("/%eccube_admin_route%/shop_event_point/camera/qr", name="admin_homepage_shop_event_point_camera_qr")
     * @Template("@admin/Customer/ShopEventPoint/camera_qr.twig")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveShopEventPointCameraQr(Request $request){

        $id = 1;
        $hash_str = md5("id:".$id);

        return [
            "login"=>1,
            "hash_str"=>$hash_str,
        ];
    }
    /**
     *
     *  カスタマー履歴を保存
     *
     * @Route("/%eccube_admin_route%/shop_event_point/save/status", name="admin_homepage_shop_event_point_save_status")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveShopEventPointStatus(Request $request){

        $id = $request->get('id');
        //$c_staff = "xxx";
        $status = $request->get('status');
        
        
        $ShopEventPointLog = $this->shopEventPointLogRepository->find($id);

        if($ShopEventPointLog == null){
            return $this->redirectToRoute('admin_customer_shop_event_point');
        }



        if($status == 1 && $ShopEventPointLog->getStatus() == 0){
            $Customer = $ShopEventPointLog->getCustomer();
    //        $Customer = $this->customerRepository->find($c_id);
    //        $ShopEventPointLog->setCustomer($Customer);
        
    //        $ShopEventPointLog->setStatus(1); // 付与済み
    //        $ShopEventPointLog->setPoint($add_point);

//                    $Customer
            $add_point = $ShopEventPointLog->getPoint();
//            $ShopEventPointLog->setStatus($status); // 承認済み
            $this->shopEventPointLogRepository->save($ShopEventPointLog);

            // Customer Point
            if($Customer){
                $point = $Customer->getPoint() + $add_point;
                $Customer->setPoint($point);
                $this->entityManager->persist($Customer);
            }                

        }

        $ShopEventPointLog->setStatus($status);
        $this->shopEventPointLogRepository->save($ShopEventPointLog);


        $this->entityManager->flush();

        return $this->redirectToRoute('admin_customer_shop_event_point');
    }  


/**
     *
     *  カスタマー履歴を保存
     *
     * @Route("/%eccube_admin_route%/shop_event_point/save/status/ajax", name="admin_homepage_shop_event_point_save_status_ajax")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveShopEventPointStatusAjax(Request $request){


        $read_text = $request->get('read_text');
        $tmp = explode(',', $read_text);
        $id = $tmp[0];
        $hash = $tmp[1];
        $status = 1;//$request->get('status');
        //$hash = $request->get('hash');


        if($hash !== md5("id:".$id)){
            return $this->json([
                "err"=>1,
                "msg"=>"有効なデータではありません",
            ]);
        }


        $err = 0;
        $s_id = $id;



        $ShopEventPointLog = $this->shopEventPointLogRepository->find($id);

        if($ShopEventPointLog == null){
            return $this->json( [
                "err"=>1,
                "msg"=>"有効なデータが見つかりませんでした",

            ]);//$this->redirectToRoute('admin_customer_shop_event_point');
        }

        $Customer = $ShopEventPointLog->getCustomer();
        $c_id = $Customer->getId();
        $price = $ShopEventPointLog->getPrice();
        $point = $ShopEventPointLog->getPoint();


        if($status == 1 && $ShopEventPointLog->getStatus() == 0){
    //        $Customer = $this->customerRepository->find($c_id);
    //        $ShopEventPointLog->setCustomer($Customer);
        
    //        $ShopEventPointLog->setStatus(1); // 付与済み
    //        $ShopEventPointLog->setPoint($add_point);

//                    $Customer
            $add_point = $ShopEventPointLog->getPoint();
//            $ShopEventPointLog->setStatus($status); // 承認済み
            $this->shopEventPointLogRepository->save($ShopEventPointLog);

            // Customer Point
            if($Customer){
                $point = $Customer->getPoint() + $add_point;
                $Customer->setPoint($point);
                $this->entityManager->persist($Customer);
            }                

        }

        $ShopEventPointLog->setStatus($status);
        $this->shopEventPointLogRepository->save($ShopEventPointLog);


        $this->entityManager->flush();

        $msg = "OK";


        return $this->json([
            "err"=>$err,
            "msg"=>$msg,
            "c_id"=>$c_id,
            "s_id"=>$s_id,
            "point"=>$point,
            "price"=>$price,
            "status" => $status,
        ]);
    }

    /**
     * カスタマー履歴一覧.
     *
     * @Route("/%eccube_admin_route%/shop_event_point", name="admin_customer_shop_event_point")
     * @Route("/%eccube_admin_route%/shop_event_point/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_customer_shop_event_point_page")
     * @Template("@admin/Customer/ShopEventPoint/index.twig")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchShopEvent(Request $request, $page_no = null, Paginator $paginator)
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
        $qb = $this->shopEventPointLogRepository->getQueryBuilderBySearchData($searchData);

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

       $c_qb = $this->shopEventPointRepository->getShopEventPoint();

        //if($c_qb->memo){
        //    $memo = json_decode($c_qb->memo);

        //}

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
            'c_qb' => $c_qb,
        ];
    }


}
