<?php

namespace Customize\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Repository\CustomerRepository;
use Eccube\Entity\Product;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Customize\Repository\SalonafResultRepository;

class SalonafPageController extends AbstractController
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var SalonafResultRepository
     */
    protected $salonafResultRepository;

    /**
     * SalonafPageController constructor.
     *
     * @param CustomerRepository $customerRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository,
        SalonafResultRepository $salonafResultRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->salonafResultRepository = $salonafResultRepository;
    }

    /**
     * @Method("GET")
     * @Route("/mypage/salonaf", name="mypage_salonaf")
     * @Template("Mypage/salonaf.twig")
     */
    public function index()
    {
        $Customer = $this->getUser();
        $member = $this->customerRepository->findBy(array('Salon_id' => $Customer['id'],'Status' => 2));
        $memberOrder = array();
        $memberOrder_temp = array();
        $monthlyAmount = array();
        $comparison = array();
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

        //受注の取得範囲を設定
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d',strtotime($startDate.' last day of this month'));
        if(!empty($_GET['startDate']) && !empty($_GET['endDate'])){
            $startDate = $_GET['startDate'];
            $endDate = $_GET['endDate'];
        }
        if(!empty($_GET['m1'])) $m1 = $_GET['m1'];
        if(!empty($_GET['m2'])) $m2 = $_GET['m2'];
        $totalInfo['range_label'] = date('Y/m/d',strtotime($startDate)).'～'.date('Y/m/d',strtotime($endDate));
        $totalInfo['startDate'] = $startDate;
        $totalInfo['endDate'] = $endDate;

        foreach ($member as $person) {
            if($person['Orders']){
                foreach ($person['Orders'] as $order) {
                    //入金済みの受注のみ対象とし、受注ステータスが"注文取り消し"の場合は除外
                    if($order['payment_date'] && $order['OrderStatus']['id'] != 3){
                        $month = $order['payment_date']->format('Y-m');
                        $date = $order['payment_date']->format('Y-m-d');
                        if(strtotime($startDate) <= strtotime($date) && strtotime($endDate) >= strtotime($date)){
                            $memberOrder_temp[$month][$date][] = $order;
                            if(!isset($monthlyAmount[$month]['sales'])) $monthlyAmount[$month]['sales'] = 0;
                            if(!isset($monthlyAmount[$month][$date]['sales'])) $monthlyAmount[$month][$date]['sales'] = 0;
                            $monthlyAmount[$month]['sales'] += $order['payment_total'];
                            $monthlyAmount[$month][$date]['sales'] += $order['payment_total'];
                            $totalInfo['total_sales'] += $order['payment_total'];
                            $totalInfo['total_order_count'] += 1;
                        }

                        //月比較
                        if(!empty($m1) && $month == $m1){
                            if(!isset($comparison[$m1][$date]['sales'])) $comparison[$m1][$date]['sales'] = 0;
                            $comparison[$m1][$date]['sales'] += $order['payment_total'];
                        }
                        if(!empty($m2) && $month == $m2){
                            if(!isset($comparison[$m2][$date]['sales'])) $comparison[$m2][$date]['sales'] = 0;
                            $comparison[$m2][$date]['sales'] += $order['payment_total'];
                        }
                    }
                }
            }
        }

        ksort($memberOrder_temp);
        foreach ($memberOrder_temp as $month => $date) {
            ksort($date);
            $memberOrder[$month] = $date;
        }

        //月ごとの売上に5%(四捨五入)加算
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
                    $month = $result['month']->format('Y年m月');
                    $paidMonth[$month]['sales'] = $result['sales'];
                    $paidMonth[$month]['reward'] = $result['reward'];
                    $paidMonth[$month]['update_date'] = $result['update_date']->format('Y年m月d日');
                }
            }
        }

        return array(
            'memberOrder' => $memberOrder,
            'monthlyAmount' => $monthlyAmount,
            'comparison' => $comparison,
            'totalInfo' => $totalInfo,
            'paidMonth' => $paidMonth
        );
    }
}