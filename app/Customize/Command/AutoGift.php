<?php

namespace Customize\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;
use Eccube\Util\StringUtil;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\AddCartType;
use Eccube\Form\Type\Admin\OrderType;
use Eccube\Form\Type\Admin\SearchCustomerType;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\OrderHelper;
use Eccube\Service\OrderStateMachine;
use Eccube\Service\PurchaseFlow\Processor\OrderNoProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseException;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\TaxRuleService;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Customize\Entity\SalonafResult;//20220725 kikuzawa
use Customize\Repository\SalonafResultRepository;//20220725 kikuzawa
use Eccube\Service\MailService;//20220725 kikuzawa

use Customize\Controller\Admin\Order\EditController;
use Plugin\MailMagazine4\Repository\MailMagazineSendHistoryRepository;
use Plugin\MailMagazine4\Service\MailMagazineService;

class AutoGift extends Command{
    

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderNoProcessor
     */
    protected $orderNoProcessor;

    /**
     * @var OrderItemTypeRepository
     */
    protected $orderItemTypeRepository;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /** @var EntityManagerInterface */
    protected $entityManager;

    //20220725 kikuzawa
    /**
     * @var SalonafResultRepository
     */
    protected $salonafResultRepository;

    //20220725 kikuzawa
    /**
     * @var MailService
     */
    protected $mailService;

    /**
     *
     */
    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        OrderStatusRepository $orderStatusRepository,
        SalonafResultRepository $salonafResultRepository,
        MailService $mailService
    )
    {
        //dump(func_get_args());
        parent::__construct();

        $this->container = $container;
        $this->mailService = $mailService;
        $this->entityManager = $entityManager; //->container->get('doctrine.orm.entity_manager');//->getManager();

        // Orderリポジトリの取得
        $this->orderRepository = $orderRepository; //this->entityManager->getRepository(\Eccube\Entity\Order::class);
        //$this->orderRepository = $orderRepository;

        $this->customerRepository = $customerRepository; //->entityManager->getRepository(\Eccube\Entity\Customer::class);
        //dump($orderRepository);
        $this->orderStatusRepository = $orderStatusRepository; //->entityManager->getRepository(\Eccube\Entity\OrderStatus::class);
        $this->salonafResultRepository = $salonafResultRepository;
    }

    protected static $defaultName = 'custom:auto_gift';

    protected function configure()
    {
        $this->setDescription('Auto gift');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //global $kernel;
        //$container = $kernel->getContainer();

        //exit;
        
        // エンティティマネージャーの取得
        //exit;
        $qb = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.OrderStatus IN (:excludes)')
                ->setParameter(':excludes', [1])
                ;
        foreach($qb->getQuery()->getResult() as $v){
            $id = $v->getId(); 
            $this->add_gift($id);
        }
    //    exit;
    //    $id = 62133;
    }

    function add_gift($id){

        try{
        //    echo sprintf('add_gift %s',$id) . PHP_EOL;
            $TargetOrder = $this->orderRepository->find($id);
            $OriginOrder = clone $TargetOrder;

       //     echo "check_gift".PHP_EOL;
            if( $this->checkGift($TargetOrder) ){
                $NewStatus = $this->orderStatusRepository->find(6);
                $TargetOrder->setOrderStatus($NewStatus);
                $memo = $TargetOrder->getNote();
                $memo = $memo ? $memo : "";
                $memo .= "\n自動ギフト受け取り";
                $TargetOrder->setNote($memo);
         //       echo $memo . PHP_EOL;

                if($TargetOrder['Customer']['salon_id'] && $TargetOrder['payment_date'] && ($NewStatus->getId() == 3 || $NewStatus->getId() == 6)){
                    $this->updateSalonafSales($TargetOrder);
                }

                $this->entityManager->flush();
            }
        }catch(Exception $e){

        }

        //$token = $container->get('security.token_storage')->getToken();
        //$user = $token->getUser();
        // order.id 62133
        //　product_class_sale_type sale type 6 ギフトカード


        echo "AutoGift End" . PHP_EOL;
    }


//アフィリエイトの報酬計算 20220722 kikuzawa
    public function updateSalonafSales($Order)
    {
        $Customer = $Order['Customer'];

        //親の存在を確認
        $parent = $this->customerRepository->findOneBy(array('id' => $Customer['salon_id'],'Status' => 2));
        if($parent){
            $payment_date = new \DateTime($Order['payment_date']->format('Y-m'));
            $payment_date_end = clone $payment_date;
            $payment_date_end->modify('+1 month');

            // 購入処理中/決済処理中ステータスの受注を非表示にする.
            $this->entityManager
                ->getFilters()
                ->enable('incomplete_order_status_hidden');

            //売上の登録の有無を確認
            $SalonafResult = $this->salonafResultRepository->findOneBy(array('Customer' => $parent, 'month' => $payment_date));
            if($SalonafResult){
                //支払済の場合は再計算の対象から除外する
                if($SalonafResult['paid_flg']){
                    $SalonafResult = '';
                }
            }
            else{
                $SalonafResult = new SalonafResult();
            }

            if($SalonafResult){
                $qb = $this->entityManager->getRepository('Eccube\Entity\Customer')
                ->createQueryBuilder('c')->select('SUM(o.payment_total)')
                ->leftJoin('c.Orders', 'o')
                ->andWhere('c.Salon_id = :parent_id')
                ->andWhere('o.payment_date IS NOT NULL')//入金日がセットされてるものを対象
                ->andWhere('o.OrderStatus <> 3')//受注ステータスから"注文取り消し"を除外
                ->andWhere('o.payment_date >= :payment_date_start')//集計期間指定
                ->andWhere('o.payment_date < :payment_date_end')
                ->setParameter('parent_id', $parent['id'])
                ->setParameter('payment_date_start', $payment_date)
                ->setParameter('payment_date_end', $payment_date_end);

                $payment_total = $qb->getQuery()->getSingleResult();
                if($payment_total){
                    $ratio = $this->eccubeConfig['eccube_salonaf_ratio'];
                    $reward = round($payment_total[1]*$ratio);
                    $SalonafResult->setCustomer($parent);
                    $SalonafResult->setMonth($payment_date);
                    $SalonafResult->setSales($payment_total[1]);
                    $SalonafResult->setReward($reward);
                    $SalonafResult->setUpdateDate(new \DateTime());
                    $this->salonafResultRepository->save($SalonafResult);
                    $this->entityManager->flush();
                }
            }
        }
    }

//購入商品にギフトが含まれるか確認してポイント付与とメール送信 20220817 kikuzawa
    public function checkGift($Order)
    {
        $flg = false;
        foreach ($Order->getOrderItems() as $Item) {
        //    echo $Item['Product']['id'] . PHP_EOL;
            if($Item['Product']['id']){
                $giftPoint = $Item['Product']->getValueData(2);
                if($giftPoint && $Item['OrderItemOptions']){
                    $giftPoint = $giftPoint * $Item['quantity'];
                    $email = '';
                    $sender = $Order['name01'].' '.$Order['name02'].'様';
                    $message = '';
                //    echo $sender . PHP_EOL;
                    foreach ($Item['OrderItemOptions'] as $option) {
                        $v = $option['OrderItemOptionCategories'][0]['value'];
                        switch ($option['option_id']) {
                            case 1:
                                $email = $v;
                                break;
                            case 2:
                                $sender = $v.'様';
                                break;
                            case 3:
                                $message = $v;
                                break;
                        }
                    }
                //    echo $email . PHP_EOL;
                //    echo sprintf('point %s', $giftPoint) . PHP_EOL;
                    $Customer = $this->customerRepository->findOneBy(array('email' => $email));

                //    echo sprintf('Customer id = %s', $Customer->getPoint()) . PHP_EOL;

                    if($Customer){
                        //ポイント付与
                        $addPoint = $Customer->getPoint() + $giftPoint;
                        echo $addPoint . PHP_EOL;
                        $Customer->setPoint($addPoint);

                   //     echo "flash" . PHP_EOL;
                   //     echo $message . PHP_EOL;
                        $this->entityManager->persist($Customer);
                        $this->entityManager->flush();


                        //メール送信
                        $this->mailService->sendGiftMail($Customer, $giftPoint, $sender, $message);
                        $flg = true;
                    }

                //    echo "sended" .PHP_EOL;
                }
            }
        }
        return $flg;
    }
}