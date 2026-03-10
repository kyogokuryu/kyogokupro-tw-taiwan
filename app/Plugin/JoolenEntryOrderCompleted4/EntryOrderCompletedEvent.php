<?php

/*
 * Plugin Name: JoolenEntryOrderCompleted4
 *
 * Copyright(c) joolen inc. All Rights Reserved.
 *
 * https://www.joolen.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JoolenEntryOrderCompleted4;

use Eccube\Common\Constant;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\EntryType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Plugin\JoolenEntryOrderCompleted4\Service\EntryOrderCompletedService;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EntryOrderCompletedEvent implements EventSubscriberInterface
{
    use ControllerTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    /**
     * @var EntryOrderCompletedService
     */
    protected $entryOrderCompletedService;

    /**
     * @var \Eccube\Service\CartService
     */
    protected $cartService;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var $session
     */
    private $session;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var string 受注IDを保持するセッションのキー
     */
    const PLG_SESSION_ORDER_ID = 'plugin.entry_order_completed.order.id';

    /**
     * EntryOrderCompletedEvent constructor.
     *
     * @param ContainerInterface $container
     * @param MailService $mailService
     * @param BaseInfoRepository $baseInfoRepository
     * @param CustomerRepository $customerRepository
     * @param CustomerStatusRepository $customerStatusRepository
     * @param OrderRepository $orderRepository
     * @param PluginRepository $pluginRepository
     * @param EntryOrderCompletedService $entryOrderCompletedService
     * @param CartService $cartService
     * @param EncoderFactoryInterface $encoderFactory
     * @param TokenStorageInterface $tokenStorage
     * @param SessionInterface $session
     * @param EntityManagerInterface $entityManager
     * @param OrderHelper $orderHelper
     * @throws \Exception
     */
    public function __construct(
        ContainerInterface $container,
        MailService $mailService,
        BaseInfoRepository $baseInfoRepository,
        CustomerRepository $customerRepository,
        CustomerStatusRepository $customerStatusRepository,
        OrderRepository $orderRepository,
        PluginRepository $pluginRepository,
        EntryOrderCompletedService $entryOrderCompletedService,
        CartService $cartService,
        EncoderFactoryInterface $encoderFactory,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        OrderHelper $orderHelper
    ) {
        $this->container = $container;
        $this->mailService = $mailService;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->customerRepository = $customerRepository;
        $this->customerStatusRepository = $customerStatusRepository;
        $this->orderRepository = $orderRepository;
        $this->pluginRepository = $pluginRepository;
        $this->entryOrderCompletedService = $entryOrderCompletedService;
        $this->cartService = $cartService;
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE => 'onShoppingCompleteInitialize'
        ];
    }

    /**
     * Kernel request listener callback.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // ShoppingController::complete以外は処理しない
        if (preg_match('/.*\/shopping\/complete/', $pathInfo) == 0) {
            return null;
        }

        // トップページへリダイレクトさせないためにセッションに受注IDをセット
        if ($request->getMethod() === 'POST') {
            $plgOrderId = $this->session->get(self::PLG_SESSION_ORDER_ID);
            $this->session->set(OrderHelper::SESSION_ORDER_ID, $plgOrderId);
        }

    }

    public function onShoppingCompleteInitialize(EventArgs $event)
    {
        $hasHiddenItemsError = false;

        /** @var $Order \Eccube\Entity\Order */
        $Order = $event->getArgument("Order");

        // ゲスト購入でなければ後続処理はしない
        if(!is_null($Order->getCustomer())) {
            return;
        }

        // 会員登録されているEmailであれば後続処理はしない
        $existingCustomer = $this->customerRepository->findBy(['email' => $Order->getEmail()]);
        if (count($existingCustomer) > 0) {
            return;
        }

        // セッションから受注IDを取得し退避
        $orderId = $this->session->get(OrderHelper::SESSION_ORDER_ID);
        $this->session->set(self::PLG_SESSION_ORDER_ID, $orderId);

        log_info('[注文完了] 購入フローのセッションをクリアします. ');
        $this->orderHelper->removeSession();

        /** @var $Customer \Eccube\Entity\Customer */
        $Customer = $this->customerRepository->newCustomer();
        $Customer = $this->entryOrderCompletedService->setFromOrder($Customer,$Order);
        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $formFactory = $this->container->get('form.factory');
        $builder = $formFactory->createBuilder(EntryType::class, $Customer);
        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();

        $request = $event->getRequest();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            log_info('会員登録開始');

            $encoder = $this->encoderFactory->getEncoder($Customer);
            $salt = $encoder->createSalt();
            $password = $encoder->encodePassword($Customer->getPassword(), $salt);
            $secretKey = $this->customerRepository->getUniqueSecretKey();

            $Customer
                ->setSalt($salt)
                ->setPassword($password)
                ->setSecretKey($secretKey)
                ->setPoint(0);

            $this->entityManager->persist($Customer);
            $this->entityManager->flush();

            log_info('会員登録完了');

            $activateFlg = $this->BaseInfo->isOptionCustomerActivate();
            $activateUrl = $this->generateUrl('entry_activate', ['secret_key' => $Customer->getSecretKey()], UrlGeneratorInterface::ABSOLUTE_URL);
            // 仮会員設定が有効な場合は、確認メールを送信し完了画面表示.
            if ($activateFlg) {

                // メール送信
                $this->mailService->sendCustomerConfirmMail($Customer, $activateUrl);

                log_info('仮会員登録完了画面へリダイレクト');

                $this->session->remove(self::PLG_SESSION_ORDER_ID);

                $response = new RedirectResponse($this->generateUrl('entry_complete', [], UrlGeneratorInterface::ABSOLUTE_URL));

                return $event->setResponse($response);
            }

            // 仮会員設定が無効な場合は、会員登録を完了させる.
            // 4.0.3 以前の処理
            if( Constant::VERSION <='4.0.3') {
                log_info('本会員登録画面へリダイレクト');
                $this->session->remove(self::PLG_SESSION_ORDER_ID);
                $response = new RedirectResponse($activateUrl);
                return $event->setResponse($response);
            }

            $qtyInCart = $this->entryActivate($request, $Customer->getSecretKey(), $event);

            $this->session->remove(self::PLG_SESSION_ORDER_ID);
            // URLを変更するため完了画面にリダイレクト
            $response = new RedirectResponse($this->generateUrl('entry_activate', [
                'secret_key' => $Customer->getSecretKey(),
                'qtyInCart' => $qtyInCart,
            ], UrlGeneratorInterface::ABSOLUTE_URL));

            return $event->setResponse($response);
        }
        foreach ($form->getErrors(true) as $error) {
            if ($error->getCause()) {
                preg_match_all(
                    '/\[.*?\]/',
                    $error->getCause()->getPropertyPath(),
                    $matches);
                $element = str_replace(['[',']'],'',array_pop($matches[0]));
            }
            if (!in_array($element, ['first','second', 'user_policy_check'])) {
                $hasHiddenItemsError = true;
                break;
            }
        }

        $hasNextCart = !empty($this->cartService->getCarts());

        $event->setResponse($this->render("Shopping/complete.twig", [
            'Order' => $Order,
            'hasNextCart' => $hasNextCart,
            'plgForm' => $form->createView(),
            'hasHiddenItemsError' => $hasHiddenItemsError,
        ]));
    }

    /**
     * 会員登録処理を行う
     *
     * @param Request $request
     * @param $secret_key
     * @param EventArgs $event
     * @return \Eccube\Entity\Cart|mixed
     */
    private function entryActivate(Request $request, $secret_key,EventArgs $event)
    {
        log_info('本会員登録開始');
        $Customer = $this->customerRepository->getProvisionalCustomerBySecretKey($secret_key);
        if (is_null($Customer)) {
            throw new HttpException\NotFoundHttpException();
        }

        $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::REGULAR);
        $Customer->setStatus($CustomerStatus);
        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        log_info('本会員登録完了');

        $event->setArgument('Customer', $Customer);
        // MEMO: 新規会員登録時ポイント付与プラグインとの連携
        $JoolenPointsForMemberRegistration4 = $this->pluginRepository->findOneBy(['code' => 'JoolenPointsForMemberRegistration4', 'enabled' => 1]);
        if ($JoolenPointsForMemberRegistration4) {
            $JoolenPointsForMemberRegistration4Event = $this->container->get('Plugin\JoolenPointsForMemberRegistration4\Event');
            $JoolenPointsForMemberRegistration4Event->onEntryActivateComplete($event);
        }

        // MEMO: 新規会員登録時クーポン付与プラグインとの連携
        $JoolenCouponForMemberRegistration4 = $this->pluginRepository->findOneBy(['code' => 'JoolenCouponForMemberRegistration4', 'enabled' => 1]);
        if ($JoolenCouponForMemberRegistration4) {
            $JoolenCouponForMemberRegistration4Event = $this->container->get('Plugin\JoolenCouponForMemberRegistration4\Event');
            $JoolenCouponForMemberRegistration4Event->onEntryActivateComplete($event);
        }

        // メール送信
        $this->mailService->sendCustomerCompleteMail($Customer);

        // Assign session carts into customer carts
        $Carts = $this->cartService->getCarts();
        $qtyInCart = 0;
        foreach ($Carts as $Cart) {
            $qtyInCart += $Cart->getTotalQuantity();
        }

        // 本会員登録してログイン状態にする
        $token = new UsernamePasswordToken($Customer, null, 'customer', ['ROLE_USER']);
        $this->tokenStorage->setToken($token);
        $request->getSession()->migrate(true);

        if ($qtyInCart) {
            $this->cartService->save();
        }

        log_info('ログイン済に変更', [$this->getUser()->getId()]);

        return $qtyInCart;

    }

}
