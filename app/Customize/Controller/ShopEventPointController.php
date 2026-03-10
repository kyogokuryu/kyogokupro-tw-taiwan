<?php

namespace Customize\Controller;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Customize\Entity\ShopEventPointLog;
use Customize\Entity\ShopEventPoint;
use Eccube\Entity\Master\CustomerStatus;
use Customize\Repository\CustomizeCustomerRepository as CustomerRepository;

use Eccube\Repository\Master\PageMaxRepository;
use Customize\Repository\ShopEventPointRepository;
use Customize\Repository\ShopEventPointLogRepository;
use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Form\Type\Front\CustomerLoginType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Eccube\Repository\Master\CustomerStatusRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;


class ShopEventPointController extends AbstractController 
{
    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;
    /**
     * @var FaqRepository
     */
    protected $shopEventPointRepository;
    protected $shopEventPointLogRepository;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;
    /**
     * @var array
     */
    protected $config;
    protected $auth_magic;
    protected $auth_type;
    protected $password_hash_algos;

    public function __construct(
        EccubeConfig $eccubeConfig,
        PageMaxRepository $pageMaxRepository,
        CustomerRepository $customerRepository,
        ShopEventPointRepository $shopEventPointRepository,
        ShopEventPointLogRepository $shopEventPointLogRepository,
        EncoderFactoryInterface $encoderFactory,
        TokenStorageInterface $tokenStorage,
        CustomerStatusRepository $customerStatusRepository
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->customerRepository = $customerRepository;
        $this->shopEventPointRepository = $shopEventPointRepository;
        $this->shopEventPointLogRepository = $shopEventPointLogRepository;
        $this->config = $eccubeConfig;
        $this->encoderFactory = $encoderFactory;
        $this->customerStatusRepository = $customerStatusRepository;

        $this->tokenStorage = $tokenStorage;

        $this->auth_magic = $eccubeConfig->get('eccube_auth_magic');
        $this->auth_type = $eccubeConfig->get('eccube_auth_type');
        $this->password_hash_algos = $eccubeConfig->get('eccube_password_hash_algos');

    }

    /**
     * @Method("GET")
     * @Route("/cm_shop_event_point", name="shop_event_point")
     * @Template("ShopEventPoint/index.twig")
     */
    public function index(Request $request)
    {

//        $raw = "testtest";
//        $Customer = $this->customerRepository->find(11);
//        $salt = $Customer->getSalt();
//        $res = $this->encodePassword($raw, $salt);
//        $res = hash_hmac($this->config['password_hash_algos'], $raw . ':' . $this->config['auth_magic'], $salt);
//var_dump([$res, $Customer->getPassword()]);
//var_dump( $res ==  $Customer->getPassword());
//var_dump($this->config);

        $shop = $this->shopEventPointRepository->find(1);

        $sdate = $shop->getE_sdate()->format('Y-m-d');
        $edate = $shop->getE_edate()->format('Y-m-d');

        $active = false;//now = date('Ymd');
        if( strtotime($sdate) <= time() && time() <= strtotime($edate) ){
            $active = true;
        }

        return [
            "active"=>$active
        ];
    }
    /**
     * Encodes the raw password.
     *
     * @param string $raw The password to encode
     * @param string $salt The salt
     *
     * @return string The encoded password
     */
    public function encodePassword($raw, $salt)
    {
        if ($salt == '') {
            $salt = $this->auth_magic;
        }
        if ($this->auth_type == 'PLAIN') {
            $res = $raw;
        } else {
            $res = hash_hmac($this->password_hash_algos, $raw.':'.$this->auth_magic, $salt);
        }

        return $res;
    }


    /**
     * @Method("GET")
     * @Route("/cm_shop_event_point_entry", name="shop_event_point_entry")
     * @Template("ShopEventPoint/entry.twig")
     */
    public function entry(Request $request)
    {



        return [
        ];
    }


    /**
     * @Method("POST")
     * @Route("/cm_shop_event_point_customer", name="shop_event_point_customer")
     */
    public function shop_event_point_customer(Request $request)
    {

        $email = $request->get('login_email');
        $pass = $request->get('login_pass');

    //    $res = hash_hmac($this->config['password_hash_algos'], $raw . ':' . $this->config['auth_magic'], $salt);

        $raw = $pass;
        $Customer = $this->customerRepository->getRegularCustomerByEmail($email);

        if($Customer == null){
            $Customer = $this->customerRepository->findOneBy(["phone_number"=>$email]);
        }

        if($Customer == null){
            return $this->json([
                "err"=>1,
                "msg"=>"登録がありません",
            ]);
        }
        $salt = $Customer->getSalt();
        $res = $this->encodePassword($raw, $salt);

        $c_id = 0;
        $err = 1;
        $msg = "";
        if($res == $Customer->getPassword()){
            $err = 0;
            $c_id = $Customer->getId();
        }else{
            $msg = "ログインできません";
        }


        return $this->json([
            "err"=>$err,
            "msg"=>$msg,
            "c_id"=>$c_id,
        ]);

    }

    /**
     * @Method("POST")
     * @Route("/cm_shop_event_point_save", name="shop_event_point_save")
     */
    public function shop_event_point_save(Request $request)
    {

        $c_id = $request->get('c_id');
        $price = $request->get('price');
        $ptype = $request->get('ptype');

        $ShopEventPointLog = null;
        // if($id){
        //    $ShopEventPointLog = $this->shopEventPointLogRepository->find($id);
        //}
        $shop = $this->shopEventPointRepository->find(1);

        // $point_value = $shop->getPointValue($price);                
        // $point  = floor($price * $point_value / 100);
        if($ptype == "2"){
            // 2倍チャージ
            $point_price = $price * 2;
            $order_price = $price; //floor($price / 2); // 購入金額
            $discount = floor($order_price / 2); // 割引金額
            $point = floor($point_price - $discount); // 付与ポイント = チャージ金額 - 購入金額の半額

        }elseif($ptype == "3"){
            // 3倍チャージ
            $point_price = $price * 3;
            $order_price = $price;//floor($price / 3);
            $discount = $order_price;
            $point = $point_price; // 付与ポイント = チャージ金額

        }else{
            return $this->json([
                "err"=>1,
                "msg"=>"チャージタイプを選択してください",
            ]);
        }


        $Customer = $this->customerRepository->find($c_id);
        if($ShopEventPointLog == null){
            $ShopEventPointLog = new ShopEventPointLog;
            $ShopEventPointLog->setCreateDate(new \DateTime());
            $ShopEventPointLog->setPrice($price);
            $ShopEventPointLog->setPoint($point);
            $ShopEventPointLog->setStatus(0);
            $ShopEventPointLog->setPtype($ptype);

            $ShopEventPointLog->setCustomer($Customer);
        }

        
        $this->shopEventPointLogRepository->save($ShopEventPointLog);
        $this->entityManager->flush();

        $err = 0;
        $msg = "";
        $c_id = $c_id;
        $s_id = $ShopEventPointLog->getId();

        $hash = $s_id.",".md5("id:".$s_id);

        $point_value = 1;

        return $this->json([
            "err"=>$err,
            "msg"=>$msg,
            "c_id"=>$c_id,
            "s_id"=>$s_id,
            "price" => $price, // 商品金額
            "price_str"=>number_format($price),
            "point" => $point,
            "point_str" => number_format($point),
            "hash" => $hash,
            "point_value"=>$point_value,
            "point_price" => $point_price,
            "point_price_val" => number_format($point_price),
            "ptype"=>$ptype,
            "discount"=>$discount,
            "order_price"=>$order_price,
            "c_pass" => sha1('id:'.$c_id),
        ]);

    }


 /**
     * @Method("POST")
     * @Route("/cm_shop_event_point_entry", name="shop_event_point_entry")
     */
    public function shop_event_point_entry(Request $request)
    {

        $name1 = $request->get('name01');
        $name2 = $request->get('name02');

        $phone = $request->get('phone');
        $email = sprintf("su.%s@example.com", $phone);
        $pass  = $phone;// substr($phone, -4, 4);


        $Customer = $this->customerRepository->findOneBy(["phone_number"=>$phone]);
        if($Customer){
            if($Customer->getName01() == $name1 && $Customer->getName02() == $name2){
            // OK
            }else{
                $err = 1;
                $msg = "別のお名前で電話番号がすでに登録済みです。";
                return $this->json([
                    "err"=>$err,
                    "msg"=>$msg
                ]);
            }
        }

        $err = 0;
        $msg = "";

        // Customer
        if($Customer){
            // 既存
        }else{
            $Customer = $this->customerRepository->newCustomer();
            $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::REGULAR);
            $Customer->setStatus($CustomerStatus);
        }
        
        $encoder = $this->encoderFactory->getEncoder($Customer);
        if ($Customer->getSalt() === null) {
            $Customer->setSalt($encoder->createSalt());
            $Customer->setSecretKey($this->customerRepository->getUniqueSecretKey());
        }
        $Customer->setName01($name1);
        $Customer->setName02($name2);
        $Customer->setPhoneNumber($phone);
        $Customer->setEntryType(1); // 店頭登録
        $Customer->setMailmagaFlg(0);

        $Customer->setEmail($email);
        
        $encPass = $encoder->encodePassword($pass, $Customer->getSalt());
        $Customer->setPassword($encPass);

        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        log_info('店頭ー会員登録完了', [$Customer->getId()]);

        $c_id = $Customer->getId();

        return $this->json([
            "err"=>$err,
            "msg"=>$msg,
            "c_id"=>$c_id,
            "pass"=>$pass,
            "phone"=>$phone,
        ]);
    }

   /**
     * ステータスチェック
     * @Method("POST")
     * @Route("/cm_shop_event_point_status_check", name="shop_event_point_status_check")
     */
    public function shop_event_point_status_check(Request $request){
        
        $s_id = $request->get('s_id');
        $ShopEventPointLog = $this->shopEventPointLogRepository->find($s_id);

        $status = 0;
        $err = 0;

        if($ShopEventPointLog){
            $status = $ShopEventPointLog->getStatus();
        }

        return $this->json([
            "err"=>$err,
            "status"=>$status
        ]);
    }

   /**
     * スタッフのパスワードチェック
     * @Method("POST")
     * @Route("/cm_shop_event_point_auth", name="shop_event_point_auth")
     */
    public function shop_event_point_auth(Request $request)
    {

        $c_id = $request->get('c_id');
        $s_id = $request->get('s_id');
        $point = $request->get('point');
        $price = $request->get('price');
        $pass = $request->get('auth');


        //$add_point = $point; //floor($price * 0.1); // ポイント１０％


        $sep = $this->active_shop_event_point();
        if($sep["active"] == false){
            $msg = "利用できません";
            $err = 2;
        }else{
            //$sep = $this->shopEventPointRepository->getShopEventPoint();
            $e_pass = $sep['e_pass'];//->getE_pass();
            $msg = "";
            if($e_pass == $pass){
                $msg = "パスワードOK";
                $err = 0;
            }else{
                $msg = "パスワードNG";
                $err = 1;
            }
        }

        $status = -1;
        if($err == 0){
            $status = 1;
            // ポイントの承認
            $ShopEventPointLog = null;
            if($s_id){
                $ShopEventPointLog = $this->shopEventPointLogRepository->find($s_id);
                
                
                if($ShopEventPointLog && $ShopEventPointLog->getStatus() == 0){
                
                    $Customer = $this->customerRepository->find($c_id);
            //        $ShopEventPointLog->setCustomer($Customer);
                
            //        $ShopEventPointLog->setStatus(1); // 付与済み
            //        $ShopEventPointLog->setPoint($add_point);

//                    $Customer
                    $add_point = $ShopEventPointLog->getPoint();
                    $ShopEventPointLog->setStatus($status); // 承認済み
                    $this->shopEventPointLogRepository->save($ShopEventPointLog);

                    // Customer Point
                    if($Customer){
                        $point = $Customer->getPoint() + $add_point;
                        $Customer->setPoint($point);
                        $this->entityManager->persist($Customer);
                    }
                    $this->entityManager->flush();
                
                    $entry_type = $Customer->getEntryType();
                }
                

                //
                //$Customer
            }
            

        }

            //return $this->redirectToRoute('shop_event_point',['msg'=>$msg]);
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


    private function active_shop_event_point(){
        $sep = $this->shopEventPointRepository->getShopEventPoint();
        $e_pass = $sep->getE_pass();

        $now = new \DateTime();
        $sdate = $sep->getE_sdate();
        $edate = $sep->getE_edate();

        return [
            'e_pass' => $e_pass,
            'active' => ($sdate <= $now && $now <= $edate),
        ];
    }

   /**
     * 簡易ログイン
     * @Method("POST")
     * @Route("/cm_shop_event_point_login", name="shop_event_point_login")
     */
    public function shop_event_point_login(Request $request)
    {

        
        $c_id = $request->get('c_id');
        $c_pass = $request->get('c_pass');
        if( sha1('id:'. $c_id) == $c_pass ){
            $Customer = $this->customerRepository->find($c_id);
            $token = new UsernamePasswordToken($Customer, null, 'customer', ['ROLE_USER']);
            $this->tokenStorage->setToken($token);

            if($Customer->isBuyReady()){
            
            }else{
            
                return $this->redirectToRoute('mypage_change');        
            }
        }
        return $this->redirectToRoute('homepage');        
        
    }
}