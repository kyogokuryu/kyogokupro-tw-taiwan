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

namespace Customize\Controller\Admin\Salonaf;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Customize\Form\SearchCustomerType;
use Customize\Repository\CustomizeCustomerRepository;
use Eccube\Repository\Master\PageMaxRepository;
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
use Customize\Entity\SalonafResult;
use Customize\Repository\SalonafResultRepository;
use Eccube\Service\MailService;

class SalonafController extends AbstractController
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
     * @var SalonafResultRepository
     */
    protected $salonafResultRepository;

    /**
     * @var MailService
     */
    protected $mailService;

    public function __construct(
        PageMaxRepository $pageMaxRepository,
        CustomizeCustomerRepository $customerRepository,
        SalonafResultRepository $salonafResultRepository,
        MailService $mailService
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->customerRepository = $customerRepository;
        $this->salonafResultRepository = $salonafResultRepository;
        $this->mailService = $mailService;
    }

    /**
     * @Route("/%eccube_admin_route%/salonaf", name="admin_salonaf")
     * @Route("/%eccube_admin_route%/salonaf/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_salonaf_page")
     * @Template("@admin/Salonaf/index.twig")
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
        $searchData['salonaf'] = true;//会員検索用のフラグ
        $qb = $this->customerRepository->getQueryBuilderBySearchData($searchData);
        $qb->orderBy('sales', 'DESC');

        $range_label = '';
        if($searchData['payment_date_start'] && $searchData['payment_date_end']){
            $payment_date_start = $searchData['payment_date_start']->format('Y年m月d日');
            $payment_date_end = $searchData['payment_date_end']->format('Y年m月t日');
        }
        else{
            $payment_date_temp = new \DateTime('first day of this month');
            $payment_date_start = $payment_date_temp->format('Y年m月d日');
            $payment_date_end = $payment_date_temp->format('Y年m月t日');
        }
        $range_label = $payment_date_start.'～'.$payment_date_end;
        //未払い検索
        $unpaid_label = '';
        if(isset($_POST['mode']) && $_POST['mode'] == 'unpaid') $unpaid_label = '未払い';

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

        //全ての売上、報酬を取得
        $totalAmount = array('sales' => 0, 'reward' => 0);
        $allCustomers = $paginator->paginate(
            $qb,
            $page_no,
            999999
        );
        if(isset($allCustomers)){
            foreach ($allCustomers as $customer) {
                $ratio = $this->eccubeConfig['eccube_salonaf_ratio'];
                $totalAmount['sales'] += $customer['sales'];
                $totalAmount['reward'] += $customer['reward'];
            }
        }

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
            'range_label' => $range_label,
            'totalAmount' => $totalAmount,
            'unpaid_label' => $unpaid_label,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/salonaf/{id}/detail", requirements={"id" = "\d+"}, name="admin_salonaf_detail")
     * @Template("@admin/Salonaf/detail.twig")
     */
    public function detail(Request $request, $id = null)
    {
        $Customer = $this->customerRepository
            ->find($id);

        if (is_null($Customer)) {
            throw new NotFoundHttpException();
        }

        //支払いステータス設定・解除
        if(isset($_POST['mode']) && $_POST['mode'] == 'edit' && isset($_POST['month'])){
            foreach ($_POST['month'] as $month => $paid_flg) {
                $objMonth = new \DateTime($month);
                // $objMonth->modify('+1 days');//9時間ずれてDBに保存されるための対策
                $SalonafResult = $this->salonafResultRepository->findOneBy(array('Customer' => $Customer, 'month' => $objMonth));
                if($SalonafResult){
                    $SalonafResult->setPaidFlg($paid_flg);
                    $SalonafResult->setUpdateDate(new \DateTime());
                    $this->salonafResultRepository->save($SalonafResult);
                    $this->entityManager->flush();

                    // メール送信
                    if($SalonafResult['paid_flg']){
                        $this->mailService->sendSalonafPaidMail($Customer, $month, $SalonafResult['sales'], $SalonafResult['reward']);
                    }
                }
            }

            return $this->redirectToRoute('admin_salonaf_detail', ['id' => $id,]);
        }

        $member = $this->customerRepository->findBy(array('Salon_id' => $Customer['id']));
        $memberOrder = array();
        $memberOrder_temp = array();
        $monthlyAmount = array();
        $totalInfo = array(
            'member_count' => count($member),
            'total_sales' => 0,
            'total_reward' => 0,
            'total_order_count' => 0
        );

        // 購入処理中/決済処理中ステータスの受注を非表示にする.
        $this->entityManager
            ->getFilters()
            ->enable('incomplete_order_status_hidden');

        foreach ($member as $person) {
            if($person['Orders']){
                foreach ($person['Orders'] as $order) {
                    //入金済みの受注のみ対象とし、受注ステータスが"注文取り消し"の場合は除外
                    if($order['payment_date'] && $order['OrderStatus']['id'] != 3){
                        $month = $order['payment_date']->format('Y-m');
                        $date = $order['payment_date']->format('Y-m-d');
                        $memberOrder_temp[$month][$date][] = $order;
                        if(!isset($monthlyAmount[$month]['sales'])) $monthlyAmount[$month]['sales'] = 0;
                        if(!isset($monthlyAmount[$month][$date]['sales'])) $monthlyAmount[$month][$date]['sales'] = 0;
                        $monthlyAmount[$month]['sales'] += $order['payment_total'];
                        $monthlyAmount[$month][$date]['sales'] += $order['payment_total'];
                        $totalInfo['total_sales'] += $order['payment_total'];
                        $totalInfo['total_order_count'] += 1;
                    }
                }
            }
        }

        krsort($memberOrder_temp);
        foreach ($memberOrder_temp as $month => $date) {
            ksort($date);
            $memberOrder[$month] = $date;
        }

        //月ごとの売上に5%(四捨五入)加算
        krsort($monthlyAmount);
        if($monthlyAmount){
            foreach ($monthlyAmount as $month => $total) {
                $ratio = $this->eccubeConfig['eccube_salonaf_ratio'];
                $monthlyAmount[$month]['reward'] = round($monthlyAmount[$month]['sales']*$ratio);
                $totalInfo['total_reward'] += round($monthlyAmount[$month]['sales']*$ratio);
            }
        }

        //支払い月の情報取得
        $paidMonth = array();
        $SalonafResult = $this->salonafResultRepository->findBy(array('Customer' => $Customer));
        if($SalonafResult){
            foreach ($SalonafResult as $result) {
                if($result['paid_flg']){
                    $paidMonth[] = $result['month']->format('Y-m');
                }
            }
        }

        return array(
            'Customer' => $Customer,
            'memberOrder' => $memberOrder,
            'monthlyAmount' => $monthlyAmount,
            'paidMonth' => $paidMonth,
            'totalInfo' => $totalInfo
        );
    }
}
