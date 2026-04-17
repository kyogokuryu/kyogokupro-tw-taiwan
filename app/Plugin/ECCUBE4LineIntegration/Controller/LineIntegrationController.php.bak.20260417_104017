<?php

namespace Plugin\ECCUBE4LineIntegration\Controller;

use Plugin\ECCUBE4LineIntegration\Consts\ApiUrl;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\CustomerStatusRepository;
use Plugin\ECCUBE4LineIntegration\Entity\LineIntegration;
use Plugin\ECCUBE4LineIntegration\Controller\Admin\LineIntegrationAdminController;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationSettingRepository;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationRepository;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Service\CartService;

class LineIntegrationController extends AbstractController
{

    private $lineChannelId;
    private $lineChannelSecret;
    private $lineIntegrationSettingRepository;
    private $lineIntegrationRepository;
    private $customerRepository;
    private $customerStatusRepository;
    private $tokenStorage;
    private $encoderFactory;
    private $cartService;
    protected $apiUrl;

    const PLUGIN_LINE_INTEGRATION_SSO_USERID = 'plugin.line_integration.sso.userid';
    const PLUGIN_LINE_INTEGRATION_SSO_STATE = 'plugin.line_integration.sso.state';

    public function __construct(
        LineIntegrationSettingRepository $lineIntegrationSettingRepository,
        LineIntegrationRepository $lineIntegrationRepository,
        CustomerRepository $customerRepository,
        CustomerStatusRepository $customerStatusRepository,
        TokenStorageInterface $tokenStorage,
        EncoderFactoryInterface $encoderFactory,
        CartService $cartService,
        ApiUrl $apiUrl
    ) {
        $this->lineIntegrationSettingRepository = $lineIntegrationSettingRepository;
        $this->lineIntegrationRepository = $lineIntegrationRepository;
        $lineIntegrationSetting = $this->getLineIntegrationSetting();
        $this->lineChannelId = $lineIntegrationSetting->getLineChannelId();
        $this->lineChannelSecret = $lineIntegrationSetting->getLineChannelSecret();
        $this->customerRepository = $customerRepository;
        $this->customerStatusRepository = $customerStatusRepository;
        $this->tokenStorage = $tokenStorage;
        $this->encoderFactory = $encoderFactory;
        $this->cartService = $cartService;
        $this->apiUrl = $apiUrl;
    }

    /**
     * ログイン画面の表示
     *
     * @Route("/plugin_line_login", name="plugin_line_login")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function login(Request $request)
    {
        $url = $this->generateUrl('plugin_line_login_callback',array(),0);
        $state = uniqid();
        $session = $request->getSession();
        $session->set(self::PLUGIN_LINE_INTEGRATION_SSO_STATE, $state);

        $previousUrl = parse_url(
            $request->headers->get('referer'),PHP_URL_PATH);
        $session->set('$previousUrl' ,$previousUrl);

        // bot_prompt
        // bot_prompt=normal or aggressive
        // https://developers.line.me/ja/docs/line-login/web/link-a-bot/
        // scope に openid%20email を追加してメールアドレス取得を試みる
        $lineAuthUrl = $this->apiUrl->getAccessUrl() . '/oauth2/v2.1/authorize?response_type=code&client_id=' . $this->lineChannelId . '&redirect_uri=' . rawurlencode($url) . '&state=' . $state . '&scope=profile%20openid%20email&bot_prompt=aggressive';

        return $this->redirect($lineAuthUrl);
    }

    /**
     * ログインのコールバック処理
     *
     * @Route("/plugin_line_login_callback", name="plugin_line_login_callback")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function loginCallback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');

        $session = $request->getSession();

        $originalState = $session->get(self::PLUGIN_LINE_INTEGRATION_SSO_STATE);
        $session->remove(self::PLUGIN_LINE_INTEGRATION_SSO_STATE);

        // APIアクセスの為のパラメータ検証
        $results = $this->validateParameter($code, $state, $originalState);
        if($results !== null) {
            return $results;
        }

        // アクセストークン発行
        $tokenJson = $this->publishAccessToken($code);
        if (isset($tokenJson['error'])) {
            //errorレスポンスはないため、この処理は起こり得ない（コード記述ミス？）
            log_error('LINE API エラー(4)' . $tokenJson['error'] . ' ' . $tokenJson['error_description']);
            return $this->render('error.twig', [
                'error_title'   => 'エラーが発生しました（エラーコード:4）',
                'error_message' => 'サイト運営者にお問い合わせください',
            ]);
        }

        if (!array_key_exists("access_token", $tokenJson)) {
            log_error('LINE API エラー(5)');
            return $this->render('error.twig', [
                'error_title'   => 'エラーが発生しました（エラーコード:5）',
                'error_message' => 'サイト運営者にお問い合わせください',
            ]);
        }

        // LineId取得
        $profile = $this->getProfile($tokenJson['access_token']);
        if (!array_key_exists("userId", $profile)) {
            log_error('LINE API エラー(6): LINE IDの取得失敗');
            return $this->render('error.twig', [
                'error_title'   => 'エラーが発生しました（エラーコード:6）',
                'error_message' => 'サイト運営者にお問い合わせください',
            ]);
        }
        if (empty($profile['userId'])) {
            //LINE API エラー(6)とほぼ同じ（コード記述ミス？）
            log_error('LINE API エラー(7): LINE IDが不正');
            return $this->render('error.twig', [
                'error_title'   => 'エラーが発生しました（エラーコード:7）',
                'error_message' => 'サイト運営者にお問い合わせください',
            ]);
        }
        $lineUserId = $profile['userId'];

        // id_tokenからメールアドレスを取得（LINE Login v2.1 OpenID Connect）
        $lineEmail = null;
        if (isset($tokenJson['id_token'])) {
            $lineEmail = $this->extractEmailFromIdToken($tokenJson['id_token']);
        }

        $session->set(self::PLUGIN_LINE_INTEGRATION_SSO_USERID, $lineUserId);
        $this->setSession($session);

        // LINE連携レコードを取得
        $lineIntegration = $this->lineIntegrationRepository->
        findOneBy(['line_user_id' => $lineUserId]);

        // LINE連携レコードの顧客IDを取得
        $lineIntegration['customer_id'] ?
            $customerId = $lineIntegration['customer_id'] :
            $customerId = null;

        // 顧客レコードから顧客取得
        $this->customerRepository->findOneBy(['id' => $customerId]) ?
            $customer =
                $this->customerRepository->findOneBy(['id' => $customerId]) :
            $customer = null;

        // LINE連携レコードがあり、LINE連携レコードに紐づく顧客レコードが見つからない場合、LINE連携レコード削除
        if (!is_null($lineIntegration)) {
            // DB上にLINE IDの登録はあるが、Customerオブジェクトが未発見の場合、LINE IDの削除
            if (is_null($customer)) {
                log_info('削除されたユーザ(customer_id:' . $customerId . ')とのLINE IDのレコードを削除します');
                $this->lineIntegrationRepository->deleteLineAssociation($lineIntegration);

                // DB上にLINE IDの登録はあるが、Customerが退会済み扱いのときも、LINE IDを削除する
            } else if ($customer->getStatus()['id'] == CustomerStatus::WITHDRAWING) {
                log_info('退会しているユーザ(customer_id:' . $customerId . ')とのLINE IDのレコードを削除します');
                $this->lineIntegrationRepository->deleteLineAssociation($lineIntegration);
                $customer = null; // 会員を存在しなかった扱いにすることで、新規登録フローに流す
            }
            // 削除後はそのままスルーし、普通のフローに
        }

        // EC-CUBEにログインしているとき（会員情報編集からの遷移）、LINE連携レコードと紐付け
        if ($this->isGranted('ROLE_USER')) {
            log_info('LINEコールバック: ログイン済み');

            if (is_null($customer)) {
                $this->associationCustomerAndLineid($lineUserId);

            } else {
                // 既にDBにLINE IDと紐づけられている顧客ID
                $registeredCustomerId = $customer->getId();
                // 新たにLINE IDと紐付けようと申請する顧客ID
                $nowLoggedInCustomerId = $this->getUser()->getId();

                if ($nowLoggedInCustomerId != $registeredCustomerId) {
                    log_info('すでに連携済みのLINE IDを別のアカウントの連携に使おうとしました', [$nowLoggedInCustomerId, $registeredCustomerId]);
                    return $this->render('error.twig', [
                        'error_title' => '重複したLINE IDです',
                        'error_message' => "既に別のアカウントで、同じLINE IDが登録されています。",
                    ]);
                }
            }
            return $this->redirectToRoute('mypage_change');
        }
        // EC-CUBEに未ログインであるとき
        else {
            log_info('LINEコールバック: 未ログイン');

            // ===== 自動会員登録: LINE連携レコードがない or 顧客レコードがない場合 =====
            if (is_null($lineIntegration) || is_null($customer)) {
                log_info('LINE自動会員登録開始: lineUserId=' . $lineUserId);

                // LINEプロフィールから名前を取得
                $displayName = isset($profile['displayName']) ? $profile['displayName'] : 'LINE會員';

                // メールアドレスの決定
                // 1. LINEから取得できた場合はそれを使用
                // 2. 取得できない場合はLINE IDベースのユニークメールを生成
                if (!empty($lineEmail)) {
                    // 既に同じメールで登録済みの顧客がいないかチェック
                    $existingCustomer = $this->customerRepository->findOneBy(['email' => $lineEmail]);
                    if (!is_null($existingCustomer)) {
                        // 既存顧客とLINE連携して自動ログイン
                        log_info('LINE自動登録: 既存メール一致 customer_id=' . $existingCustomer->getId());
                        $customer = $existingCustomer;
                        // LINE連携レコードを作成
                        $this->createLineAssociation($lineUserId, $customer->getId());
                        return $this->autoLoginAndRedirect($request, $customer, $session);
                    }
                    $email = $lineEmail;
                } else {
                    // LINEからメール取得できない場合、ユニークなダミーメールを生成
                    $email = 'line_' . substr($lineUserId, 1, 16) . '@line.user';
                    // 既に同じダミーメールで登録済みかチェック
                    $existingCustomer = $this->customerRepository->findOneBy(['email' => $email]);
                    if (!is_null($existingCustomer)) {
                        log_info('LINE自動登録: 既存ダミーメール一致 customer_id=' . $existingCustomer->getId());
                        $customer = $existingCustomer;
                        $this->createLineAssociation($lineUserId, $customer->getId());
                        return $this->autoLoginAndRedirect($request, $customer, $session);
                    }
                }

                // 新規顧客を作成
                $Customer = new \Eccube\Entity\Customer();
                $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::REGULAR);

                // パスワード生成（ランダム）
                $encoder = $this->encoderFactory->getEncoder($Customer);
                $salt = $encoder->createSalt();
                $randomPassword = bin2hex(random_bytes(16));
                $password = $encoder->encodePassword($randomPassword, $salt);
                $secretKey = $this->customerRepository->getUniqueSecretKey();

                // 500ポイント付与（通常の新規登録と同じ）
                $addPoint = 500;

                $now = new \DateTime();
                $Customer
                    ->setName01($displayName)
                    ->setName02('')
                    ->setEmail($email)
                    ->setSalt($salt)
                    ->setPassword($password)
                    ->setSecretKey($secretKey)
                    ->setPoint($addPoint)
                    ->setStatus($CustomerStatus)
                    ->setCreateDate($now)
                    ->setUpdateDate($now);

                $this->entityManager->persist($Customer);
                $this->entityManager->flush();

                log_info('LINE自動会員登録完了: customer_id=' . $Customer->getId() . ', email=' . $email);

                // LINE連携レコードを作成
                $this->createLineAssociation($lineUserId, $Customer->getId());

                // ポイント履歴を記録
                $this->recordPointLog($Customer->getId(), 0, $addPoint, 'LINE登入新會員500點贈送');

                // 自動ログインしてマイページへ
                return $this->autoLoginAndRedirect($request, $Customer, $session);
            }

            // 仮会員の場合ログインへ
            if ($customer->getStatus()->getId() == 1) {
                log_info('仮会員のため、ログインへ customer_id:'.$customerId);

                if (substr($session->get('$previousUrl'), -15) == '/shopping/login') {
                    return $this->redirectToRoute('shopping_login');
                }

                return $this->redirectToRoute('mypage_login');
            }

            // 本会員かつ、LINE連携レコード・顧客レコードが存在するのでログイン処理
            if ($customer->getStatus()->getId() == 2) {
                return $this->autoLoginAndRedirect($request, $customer, $session);
            }

            // 例外としてログインページに戻す
            return $this->redirectToRoute('login');
        }
    }

    /**
     * 自動ログインしてリダイレクト
     */
    private function autoLoginAndRedirect(Request $request, $customer, $session)
    {
        $token = new UsernamePasswordToken($customer, null, 'customer',
            array('ROLE_USER'));
        $this->tokenStorage->setToken($token);
        log_info('ログイン済に変更。dtb_customer.id:'.$this->getUser()->getId());

        // カートのマージなどの処理
        $loginEvent = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch(
            SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);

        // 遷移元がカート経由のログインだった場合、購入画面へ
        if (substr($session->get('$previousUrl'), -15) == '/shopping/login') {
            return $this->redirectToRoute('shopping');
        }
        // かご落ちメッセージ経由のログインだった場合、カート画面（セッションに保存されているURL）へ
        if ($session->get('dropped-cart-notifier-redirect') !== null) {
            return $this->redirect($session->get('dropped-cart-notifier-redirect'));
        }
        // そうでない場合 feed ページへ遷移
        return $this->redirect('/feed/');
    }

    /**
     * LINE連携レコードを作成
     */
    private function createLineAssociation($lineUserId, $customerId)
    {
        // 既存の連携レコードがないか確認
        $existing = $this->lineIntegrationRepository->findOneBy(['line_user_id' => $lineUserId]);
        if (!is_null($existing)) {
            log_info('LINE連携レコード既存: line_user_id=' . $lineUserId);
            return;
        }

        $lineIntegration = new LineIntegration();
        $lineIntegration->setLineUserId($lineUserId);
        $lineIntegration->setCustomerId($customerId);
        $lineIntegration->setLineNotificationFlg(1);
        $lineIntegration->setDelFlg(0);
        $this->entityManager->persist($lineIntegration);
        $this->entityManager->flush();
        log_info('LINE連携レコード作成完了: customer_id=' . $customerId);
    }

    /**
     * ポイント履歴を記録
     */
    private function recordPointLog($customerId, $pointBefore, $pointAfter, $memo)
    {
        try {
            $conn = $this->entityManager->getConnection();
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $conn->executeUpdate(
                "INSERT INTO dtb_point_log (customer_id, point1, point2, memo, create_date, update_date, discriminator_type) VALUES (?, ?, ?, ?, ?, ?, 'pointlog')",
                [$customerId, $pointBefore, $pointAfter, $memo, $now, $now]
            );
            log_info('ポイント履歴記録完了: customer_id=' . $customerId . ', point=' . $pointAfter);
        } catch (\Exception $e) {
            log_error('ポイント履歴記録失敗: ' . $e->getMessage());
        }
    }

    /**
     * id_tokenからメールアドレスを抽出（JWT decode without verification）
     */
    private function extractEmailFromIdToken($idToken)
    {
        try {
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                return null;
            }
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            if (isset($payload['email'])) {
                return $payload['email'];
            }
        } catch (\Exception $e) {
            log_error('id_tokenからのメール取得失敗: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * 設定レコードを取得します
     * @return string
     */
    private function getLineIntegrationSetting()
    {
        $lineIntegrationSetting = $this->lineIntegrationSettingRepository
            ->find(LineIntegrationAdminController::LINE_INTEGRATION_SETTING_TABLE_ID);

        return $lineIntegrationSetting;
    }

    /**
     * LINE APIからアクセストークンを取得する為の、パラメータを検証します
     * @param $code
     * @param $state
     * @param $originalState
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    private function validateParameter($code, $state, $originalState){

        if (empty($code)) {
            log_error('LINE API エラー(0): 認可コードが空');
            $config = $this->lineIntegrationSettingRepository->find(1);
            if (is_null($config) || is_null($config->getLineAddCancelRedirectUrl())) {
                log_error("[LineIntegration] 設定を取得できませんでした");
                return $this->render('error.twig', [
                    'error_title'   => 'エラーが発生しました（エラーコード:0）',
                    'error_message' => 'サイト運営者にお問い合わせください',
                ]);
            } else {
                return $this->redirect($config->getLineAddCancelRedirectUrl());
            }
        }
        if (empty($state)) {
            log_error('LINE API エラー(1): CSRF防止用の固有な英数字の文字列が空');
            return $this->render('error.twig', [
                'error_title'   => 'エラーが発生しました（エラーコード:1）',
                'error_message' => 'サイト運営者にお問い合わせください',
            ]);
        }
        if (empty($originalState)) {
            log_error('LINE API エラー(2): セッションタイムアウト');
            return $this->render('error.twig', [
                'error_title'   => 'エラーが発生しました（エラーコード:2）',
                'error_message' => 'セッションタイムアウトしました。再度ログインしてください。',
            ]);
        }
        if ($state != $originalState) {
            log_error('LINE API エラー(3): CSRF防止用の固有な英数字の文字列がセッションのものと異なる');
            return $this->render('error.twig', [
                'error_title'   => 'エラーが発生しました（エラーコード:3）',
                'error_message' => 'サイト運営者にお問い合わせください',
            ]);
        }
        return null;
    }

    /**
     * LINE APIでアクセストークンを発行します
     * @param $code
     *
     * @return mixed
     */
    private function publishAccessToken($code){
        $url = $this->generateUrl('plugin_line_login_callback',array(),0);
        $accessTokenUrl = $this->apiUrl->getApiUrl() . "/oauth2/v2.1/token";
        $accessTokenData = array(
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => $url,
            "client_id" => $this->lineChannelId,
            "client_secret" => $this->lineChannelSecret,
        );
        $accessTokenData = http_build_query($accessTokenData, "", "&");
        $header = array(
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . strlen($accessTokenData)
        );
        $context = array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => $accessTokenData
            )
        );

        $response = file_get_contents($accessTokenUrl, false, stream_context_create($context));
        $tokenJson = json_decode($response, true);

        return $tokenJson;
    }

    /**
     * LINE APIからLINE IDを取得します
     * @param $accessToken
     *
     * @return mixed
     */
    private function getProfile($accessToken){
        $lineProfileUrl = $this->apiUrl->getApiUrl() . "/v2/profile";
        $context = array(
            "http" => array(
                "method" => "GET",
                "header" => "Authorization: Bearer " . $accessToken
            )
        );

        $response = file_get_contents($lineProfileUrl, false, stream_context_create($context));
        $profileJson = json_decode($response, true);

        return $profileJson;
    }

    /**
     * 顧客とLINE連携レコードの紐付けを行います
     * @param $customer
     * @param $lineUserId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function associationCustomerAndLineid($lineUserId){
        log_info('plg_line_integrationレコードなし');
        $lineIntegration = new LineIntegration();
        $lineIntegration->setLineUserId($lineUserId);
        $lineIntegration->setCustomerId($this->getUser()->getId());
        $lineIntegration->setLineNotificationFlg(1);
        $lineIntegration->setDelFlg(0);
        $this->entityManager->persist($lineIntegration);
        $this->entityManager->flush();
        log_info('LINE IDとユーザーの関連付け終了');
    }
}
