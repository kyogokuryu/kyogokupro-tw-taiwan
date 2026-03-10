<?php

namespace Plugin\ECCUBE4LineIntegration;

use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EccubeEvents;
use Eccube\Entity\Master\CustomerStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationRepository;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationSettingRepository;
use Plugin\ECCUBE4LineIntegration\Controller\Admin\LineIntegrationAdminController;
use Plugin\ECCUBE4LineIntegration\Controller\LineIntegrationController;
use Plugin\ECCUBE4LineIntegration\Entity\LineIntegration;
use Twig_Environment;

class LineIntegrationEvent implements EventSubscriberInterface
{
    private $lineIntegrationRepository;
    private $lineIntegrationSettingRepository;
    private $lineIntegration;
    private $container;
    private $router;
    private $session;
    private $entityManager;
    private $formFactory;
    private $twig;

    public function __construct(
        LineIntegrationRepository $lineIntegrationRepository,
        LineIntegrationSettingRepository $lineIntegrationSettingRepository,
        ContainerInterface $container,
        Twig_Environment $twig
    ) {
        $this->lineIntegrationRepository = $lineIntegrationRepository;
        $this->lineIntegrationSettingRepository = $lineIntegrationSettingRepository;
        $this->container = $container;
        $this->router = $this->container->get('router');
        $this->session = $this->container->get('session');
        $this->entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $this->formFactory = $this->container->get('form.factory');
        $this->twig = $twig;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Entry/index.twig' => [
                ['onRenderEntryIndex',10],
                ['onRenderLineEntryButton',-10]
            ],
            EccubeEvents::FRONT_ENTRY_INDEX_COMPLETE => 'onCompleteEntry',
            'Mypage/login.twig' => 'onRenderLineLoginButton',
            'Mypage/change.twig' => 'onRenderMypageChange',
            'Shopping/login.twig' => 'onRenderShoppingLineLoginButton',
            'Product/supplier_login.twig' => 'onRenderSupplierLineLoginButton',
            EccubeEvents::FRONT_MYPAGE_CHANGE_INDEX_COMPLETE => 'onCompleteMypageChange',
            EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_COMPLETE => 'onCompleteMypageWithdraw',
            EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_COMPLETE => 'onCompleteCustomerEdit',
        ];
    }

    /**
     * 新規会員登録画面の表示
     *
     * @param TemplateEvent $event
     * @throws \Twig_Error_Loader
     */
    public function onRenderEntryIndex(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $lineUserId = $this->session
            ->get(LineIntegrationController::PLUGIN_LINE_INTEGRATION_SSO_USERID);
        if (!empty($lineUserId)) {
            // LINE通知のチェックボックスを表示
            // →現状では表示しても無視されてしまうので表示しないように
            $this->replaceTwig($event, 'entry_add_line_notification.twig');
        }
    }

    /**
     * 新規会員登録画面へLINEボタンを出力
     *
     * @param TemplateEvent $event
     */
    public function onRenderLineEntryButton(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $lineUserId = $this->session
            ->get(LineIntegrationController::PLUGIN_LINE_INTEGRATION_SSO_USERID);

        // $linkUrl = $this->router->generate("plugin_line_login", array(),
        //     UrlGeneratorInterface::ABSOLUTE_URL);
        // $imgUrl = $this->router->generate("homepage", array(),
        //         UrlGeneratorInterface::ABSOLUTE_URL)
        //     . 'html/plugin/line_integration/assets/img/btn_register_base.svg';

        $snipet = '';
        // LINEボタンを表示
        // if (empty($lineUserId)) {
        //     $snipet .= '<div class="btn" style="display:block;margin:auto;margin-top:-2em"><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEで登録"></a></div>' . PHP_EOL;
        //     $snipet .= PHP_EOL;
        // }
        // // LINEにログイン済みなので登録を促す
        // else {
        //     $snipet .= '<div class="col" style="margin-top:-10px; padding:10px;">LINEログイン済みです。この会員登録が完了すると、LINEでログインできるようになります。</div>';
        //     $snipet .= PHP_EOL;
        // }

        $search = '<div class="ec-off1Grid__cell">';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * 会員登録処理完了時のLINE連携解除処理
     *
     * @param EventArgs $event
     */
    public function onCompleteEntry(EventArgs $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $lineUserId = $this->session
            ->get(LineIntegrationController::PLUGIN_LINE_INTEGRATION_SSO_USERID);
        if (!empty($lineUserId)) {
            // 顧客とLINEユーザーIDをひも付け（line_integrationテーブルのレコードを作成）
            log_info('LINEログインしているため、ユーザーとの関連付けを実行');

            $this->lineIntegration = $this->lineIntegrationRepository
                ->findOneBy(['line_user_id' => $lineUserId]);
            $line_notificationFlg = $event['form']->get('line_notification_flg')->getData();

            if (empty($this->lineIntegration)) {
                $customer = $event['Customer'];
                log_info('LINE IDとユーザーの関連付けを開始', [$customer['id']]);
                $lineIntegration = new LineIntegration();
                $lineIntegration->setLineUserId($lineUserId);
                $lineIntegration->setLineNotificationFlg($line_notificationFlg);
                $lineIntegration->setDelFlg(0);
                $lineIntegration->setCustomer($customer);
                $lineIntegration->setCustomerId($customer['id']);
                $this->entityManager->persist($lineIntegration);
                $this->entityManager->flush($lineIntegration);
                log_info('LINEユーザーとの関連付け終了');
            } else {
                log_error('新規登録フローで、既に関連付けされているLINE IDを検知。');
            }
        } else {
            log_info('LINE未ログインのため関連付け未実施');
        }
    }

    /**
     * ログイン画面へLINEボタンを出力
     *
     * @param TemplateEvent $event
     */
    public function onRenderLineLoginButton(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $linkUrl = $this->router->generate("plugin_line_login",array(),
            UrlGeneratorInterface::ABSOLUTE_URL);
        $imgUrl = $this->router->generate("homepage",array(),
                UrlGeneratorInterface::ABSOLUTE_URL)
            .'html/plugin/line_integration/assets/img/line_login_btn.svg';
        $snipet = '<div class="btn" style=""><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEログイン"></a></div><br>' . PHP_EOL;
        // $snipet .= '<div class="col" style="margin-top:-10px; padding:10px;">ログイン後にマイページからも「LINEでログイン」の設定がおこなえます。</div>';
        $search = '<div class="ec-off2Grid__cell">';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * カート経由のログイン画面にLINEボタンを出力します
     * @param TemplateEvent $event
     */
    public function onRenderShoppingLineLoginButton(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $linkUrl = $this->router->generate("plugin_line_login", array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $imgUrl = $this->router->generate("homepage", array(),
                UrlGeneratorInterface::ABSOLUTE_URL) . 'html/plugin/line_integration/assets/img/line_login_btn.svg';
        // $snipet = '<div class="btn" style=""><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEログイン"></a></div><br>' . PHP_EOL;
        $search = '<div class="ec-off2Grid__cell">';
        $replace = $search;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * 会員情報変更画面の表示
     *
     * @param TemplateEvent $event
     * @throws \Twig_Error_Loader
     */
    public function onRenderMypageChange(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $form = $event->getParameter('form');
        $customerId = $form->vars['value']['id'];
        if (empty($customerId)) {
            error_log("会員IDを取得できませんでした", [$form]);
            return;
        }

        $lineIntegration = $this->lineIntegrationRepository
            ->findOneBy(['customer_id' => $customerId]);
        $lineIdBySession = $this->session
            ->get(LineIntegrationController::PLUGIN_LINE_INTEGRATION_SSO_USERID);
        // LINEとの紐づけがないとき
        if (empty($lineIntegration)) {
            // LINEのログインボタン表示
            $linkUrl = $this->router->generate("plugin_line_login", array(),
                UrlGeneratorInterface::ABSOLUTE_URL);
            $imgUrl = $this->router->generate("homepage", array(),
                    UrlGeneratorInterface::ABSOLUTE_URL) . 'html/plugin/line_integration/assets/img/btn_register_base.png';
            $snipet = '<div class="btn"></div>' . PHP_EOL;
            $snipet .= PHP_EOL;
            $snipet .= '<div></div>';
            $snipet .= PHP_EOL;
        }
        // LINEとの紐づけがあっても、現在LINEにログインしていないっぽいとき
        else if (empty($lineIdBySession)) {
            // LINEのログインボタン表示
            $linkUrl = $this->router->generate("plugin_line_login", array(),
                UrlGeneratorInterface::ABSOLUTE_URL);
            $imgUrl = $this->router->generate("homepage", array(),
                    UrlGeneratorInterface::ABSOLUTE_URL) . 'html/plugin/line_integration/assets/img/btn_register_base.png';
            $snipet = '<div class="btn"><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEで登録"></a></div>' . PHP_EOL;
            $snipet .= PHP_EOL;
            $snipet .= '<div class="col" style="padding-bottom:10px;">LINEアカウントと連携済みですが、現在LINEでログインしていません。</div>';
            $snipet .= PHP_EOL;
        }
        //LINEとの紐づけがあって、かつLINEにログイン中のとき
        else {
            // 連携解除項目を追加
            $this->replaceTwig($event,'mypage_change_add_is_line_delete.twig');
            // 上部にLINE連携済みである旨を通知
            $snipet = '<div class="col" style="padding-bottom:10px;">LINEアカウント連携済です。解除したいときは「LINE連携 解除」をチェックして「登録する」ボタンを押してください。</div>';
            $snipet .= PHP_EOL;
        }

        $search = '<div class="ec-off1Grid__cell">';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);

        // LINE通知のチェックボックスを表示
        $this->replaceTwig($event, 'entry_add_line_notification.twig');
    }

    /**
     * 会員情報編集完了時の処理
     *
     * @param EventArgs $event
     */
    public function onCompleteMypageChange(EventArgs $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $customerId = $event['Customer']->getId();
        $lineIntegration = $this->lineIntegrationRepository
            ->findOneBy(['customer_id' => $customerId]);

        // LINEの紐づけがすでにあるとき
        if (!empty($lineIntegration)) {
            $form = $event['form'];

            if ($form->has('line_notification_flg')) {
                $line_notification_flg = $form->get('line_notification_flg')->getData();
                $lineIntegration->setLineNotificationFlg($line_notification_flg);
                $this->entityManager->persist($lineIntegration);
                $this->entityManager->flush();
            }

            // LINE情報を削除する
            if ($form->has('is_line_delete')) {
                $is_line_delete = $form->get('is_line_delete')->getData();
            }
            if ($is_line_delete == 1) {
                // 連携解除
                $this->lineIdUnassociate($customerId, true);
            }

        }
        // LINEの紐づけがないとき
        else {
            // 何もしない
            // LINEとの紐づけ処理はログインのコールバック関数(LineIntegrationController.php)内で行われるのでここでは行わない
        }
    }

    /**
     * マイページの退会手続き完了時の処理
     *
     * 会員がマイページから退会手続きを行ったとき、退会した会員のLINE連携を解除する
     *
     * @param EventArgs $event
     */
    public function onCompleteMypageWithdraw(EventArgs $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        log_info('マイページから退会');
        $customerId = $event['Customer']['id'];
        $this->lineIdUnassociate($customerId, true);
    }

    /**
     * 管理画面で会員情報を更新したときの処理
     *
     * 会員を退会にした場合にはLINE連携を解除する
     *
     * @param EventArgs $event
     */
    public function onCompleteCustomerEdit(EventArgs $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $customerId = $event['Customer']->getId();
        $customerStatus = $event['Customer']->getStatus();
        // 退会扱いのとき
        if ($customerStatus['id'] == CustomerStatus::WITHDRAWING) {
            log_info('仮画面の会員情報編集ページから退会扱い');
            $this->lineIdUnassociate($customerId);
        }
    }


    /**
     * LINE設定が初期化済みかチェックする
     */
    private function isLineSettingCompleted()
    {
        $lineIntegrationSetting = $this->lineIntegrationSettingRepository->find(LineIntegrationAdminController::LINE_INTEGRATION_SETTING_TABLE_ID);

        if (empty($lineIntegrationSetting)) {
            log_error("Line Lineの情報が未設定です");
            return false;
        }

        $lineChannelId = $lineIntegrationSetting->getLineChannelId();
        if (empty($lineChannelId)) {
            log_error("Line Channel Idが未設定です");
            return false;
        }

        $lineChannelSecret = $lineIntegrationSetting->getLineChannelSecret();
        if (empty($lineChannelSecret)) {
            log_error("Line Channel Secretが未設定です");
            return false;
        }

        return true;
    }


    /**
     * LINEアカウントとの連携を解除する処理
     *
     * 会員IDから連携DBを検索し、該当するレコードを削除する処理。管理画面でなくフロントからのフローでは、
     * セッションを削除するのでフラグをtrueにしておく
     *
     * @param int $customerId       LINEとの連携を解除したい会員ID
     * @param bool $isDeleteSession セッションまで削除する。デフォでfalse
     * @return bool                 会員がLINEと紐づけされていて、紐づけを解除したときにtrueを返す
     */
    private function lineIdUnassociate(int $customerId, ?bool $isDeleteSession = null) {
        $lineIntegration = $this->lineIntegrationRepository
            ->findOneBy(['customer_id' => $customerId]);
        // LINE情報を削除する
        if (!empty($lineIntegration)) {
            log_info('customer_id:' . $customerId . 'のLINE連携を解除');
            $this->lineIntegrationRepository->deleteLineAssociation($lineIntegration);
            log_info('LINEの連携を解除しました');

            if ($isDeleteSession) {
                $this->session
                    ->remove(LineIntegrationController::PLUGIN_LINE_INTEGRATION_SSO_STATE);
                $this->session
                    ->remove(LineIntegrationController::PLUGIN_LINE_INTEGRATION_SSO_USERID);
            }
            return true;
        }
        return false;
    }


    /**
     * twigテンプレートの追加をおこなう
     *
     * @param TemplateEvent $event
     * @param string $twigName
     * @throws \Twig_Error_Loader
     */
    private function replaceTwig(TemplateEvent $event, string $twigName)
    {
        $snippet = $this->twig->getLoader()
            ->getSourceContext('ECCUBE4LineIntegration/Resource/template/' .
            $twigName)->getCode();
        $search = '{# エンティティ拡張の自動出力 #}';
        $replace = $snippet . $search;  //無料版での「LINE連携削除」チェックボックスでは2つが逆順
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }

    /**
     * ログイン画面へLINEボタンを出力
     *
     * @param TemplateEvent $event
     */
    public function onRenderSupplierLineLoginButton(TemplateEvent $event)
    {
        if (!$this->isLineSettingCompleted()) {
            return;
        }

        $linkUrl = $this->router->generate("plugin_line_login",array(),
            UrlGeneratorInterface::ABSOLUTE_URL);
        $imgUrl = $this->router->generate("homepage",array(),
                UrlGeneratorInterface::ABSOLUTE_URL)
            .'html/plugin/line_integration/assets/img/line_login_btn.svg';
        $snipet = '<div class="btn" style=""><a href="' . $linkUrl . '" class="line-button"><img src="' . $imgUrl . '" alt="LINEログイン"></a></div><br>' . PHP_EOL;
        // $snipet .= '<div class="col" style="margin-top:-10px; padding:10px;">ログイン後にマイページからも「LINEでログイン」の設定がおこなえます。</div>';
        $search = '<div class="ec-off2Grid__cell">';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $event->getSource());
        $event->setSource($source);
    }
}
