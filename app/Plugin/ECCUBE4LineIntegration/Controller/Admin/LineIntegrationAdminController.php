<?php

namespace Plugin\ECCUBE4LineIntegration\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\PageMaxRepository;
use Plugin\ECCUBE4LineIntegration\Entity\LineIntegrationSetting;
use Plugin\ECCUBE4LineIntegration\Entity\LineIntegrationHistory;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationSettingRepository;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationHistoryRepository;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationRepository;
use Plugin\ECCUBE4LineIntegration\Form\Type\LineSettingType;
use Plugin\ECCUBE4LineIntegration\Form\Type\LineSearchType;
use Plugin\ECCUBE4LineIntegration\Form\Type\LineMessageType;
use Plugin\ECCUBE4LineIntegration\Service\CronManageService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Eccube\Common\Constant;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Knp\Component\Pager\PaginatorInterface;

class LineIntegrationAdminController extends AbstractController
{
    private $lineIntegrationSettingRepository;
    private $lineIntegrationHistoryRepository;
    private $lineIntegrationRepository;
    private $pageMaxRepository;

    const LINE_INTEGRATION_SETTING_TABLE_ID = 1;

    public function __construct(
        LineIntegrationSettingRepository $lineIntegrationSettingRepository,
        LineIntegrationHistoryRepository $lineIntegrationHistoryRepository,
        LineIntegrationRepository $lineIntegrationRepository,
        PageMaxRepository $pageMaxRepository
    ) {
        $this->lineIntegrationSettingRepository = $lineIntegrationSettingRepository;
        $this->lineIntegrationHistoryRepository = $lineIntegrationHistoryRepository;
        $this->lineIntegrationRepository = $lineIntegrationRepository;
        $this->pageMaxRepository = $pageMaxRepository;
    }

    // CustomerRepositoryをLineIntegrationRepositoryから操作する方法が思いつかなかったので苦し紛れのアクセサ
    public function getCustomerRepository() {
        return $this->entityManager->getRepository('Eccube\Entity\Customer');
    }


    /**
     * 会員の検索画面
     *
     * @Route("/%eccube_admin_route%/plugin_line_message_search", name="plugin_line_message_search")
     * @Template("@ECCUBE4LineIntegration/admin/search.twig")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return array
     */
    public function search(Request $request, PaginatorInterface $paginator)
    {
        if (!$this->isTokenSettingCompleted()) {
            log_error('アクセストークンが設定されていません(LineIntegrationAdminController#search)');
            return $this->render('ECCUBE4LineIntegration/Resource/template/admin/search.nosetting.twig');
        }

        $searchForm = $this->formFactory
            ->createBuilder(LineSearchType::class)
            ->getForm();

        // POSTされたなら条件で検索する
        if ('POST' === $request->getMethod()) {
            $pageCount = $this->eccubeConfig->get('eccube_search_pmax');
            $pageMaxis = $this->pageMaxRepository->findAll();

            $searchForm->handleRequest($request);
            $searchData = array();
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
            }
            // 検索条件をセッションに保管 → 送信時に利用
            $this->session->set('plugin.line_integration.search', $searchData);

            $messageForm = $this->formFactory
                ->createBuilder(LineMessageType::class)
                ->getForm();
            $messageForm->handleRequest($request);

            $this->lineIntegrationRepository->setApplication($this);
            $queryBuilder = $this->lineIntegrationRepository->getQueryBuilderBySearchData($searchData);

            $pagination = $paginator->paginate(
                $queryBuilder, empty($searchData['pageno']) ? 1 : $searchData['pageno'], $pageCount
            );

            return [
                'searchForm' => $searchForm->createView(),
                'messageForm' => $messageForm->createView(),
                'pagination' => $pagination,
                'pageMaxis' => $pageMaxis,
                'page_count' => $pageCount,
            ];
        }
        // POSTでなければ検索フォームだけ表示
        else {
            return [
                'searchForm' => $searchForm->createView(),
            ];
        }
    }


    /**
     * メッセージ送信に利用する画像ファイルをアップロードします
     *
     * システムのtmepディレクトリからEC-CUBE上のtempディレクトリに、画像ファイルを移動する
     *
     * @Route("/%eccube_admin_route%/plugin_line_message_notification_imagefile_upload/", name="plugin_line_message_notification_imagefile_upload")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function imageAdd(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('line_message');

        $filename = null;
        if (isset($images['image_file'])) {
            $image = $images['image_file'];

            //ファイルフォーマット検証
            $mimeType = $image->getMimeType();
            if (0 !== strpos($mimeType, 'image')) {
                throw new UnsupportedMediaTypeHttpException();
            }

            $extension = $image->guessExtension();
            $filename = date('mdHis') . uniqid('_') . '.' . $extension;
            $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
        }

        if (isset($images['carousel_columns'])) {
            foreach ($images['carousel_columns'] as $column) {
                $image = $column['image_file'];
                //ファイルフォーマット検証
                $mimeType = $image->getMimeType();
                if (0 !== strpos($mimeType, 'image')) {
                    throw new UnsupportedMediaTypeHttpException();
                }

                $extension = $image->guessExtension();
                $filename = date('mdHis') . uniqid('_') . '.' . $extension;
                $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
            }
        }

        return $this->json(['filename' => $filename], 200);
    }


    /**
     * アップロードされている画像をsaveディレクトリに移動する
     *
     * EC-CUBE上のtempディレクトリから、プラグインのsaveディレクトリに画像ファイルを移動する。
     * 例) eccube_temp_image_dir/hoge.jpeg → eccube_save_image_dir/line_integration/hoge.jpeg
     *
     * @param $fileName 対象のファイル名(tempディレクトリ内のファイル名)
     * @return string   移動した後のファイル名 (saveディレクトリからみた相対パス)
     */
    private function moveFileTempToSave($fileName) {
        $uploadDirName = 'line_integration';
        $uploadDirPath = $this->eccubeConfig['eccube_save_image_dir'] . DIRECTORY_SEPARATOR . $uploadDirName;

        // LINE Integration用アップロードディレクトリの作成
        $filesystem = new Filesystem();
        if (!$filesystem->exists($uploadDirPath)) {
            log_info("ディレクトリ " . $uploadDirPath . "を作成します");
            $filesystem->mkdir($uploadDirPath);
            chmod($uploadDirPath, 0777);
            log_info("ディレクトリを作成しました");
        }

        $uploadedTempImagePath = $this->eccubeConfig['eccube_temp_image_dir'] . '/' . $fileName;
        // ファイルの移動
        $file = new File($uploadedTempImagePath);
        log_info('画像ファイルを移動します', [$uploadedTempImagePath, $uploadDirPath, $fileName]);
        $file->move($uploadDirPath, $fileName);

        return $uploadDirName . "/" . $fileName; // $uploadDirName は定数なので返り値を使うか微妙
    }


    /**
     * メッセージの送信処理
     *
     * @Route("/%eccube_admin_route%/plugin_line_message_notification", name="plugin_line_message_notification")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function message(Request $request)
    {
        if ('https' !== $request->getScheme()) {
            log_error('https以外でのリクエストが送信されました(LineIntegrationAdminController#message)');
            throw new BadRequestHttpException();
        }

        // 各種エラーチェック
        if (!$this->isTokenSettingCompleted()) {
            log_error('アクセストークンが設定されていません(LineIntegrationAdminController#message)');
            throw new BadRequestHttpException();
        }

        if ('POST' !== $request->getMethod()) {
            log_error('POST以外のリクエストが送信されました(LineIntegrationAdminController#message)');
            throw new BadRequestHttpException();
        }

        $accessToken = $this->getLineIntegrationSetting()->getLineAccessToken();
        if (empty($accessToken)) {
            log_error('アクセストークンが取得できませんでした(LineIntegrationAdminController#message)');
            throw new BadRequestHttpException();
        }

        $messageForm = $this->formFactory->createBuilder(LineMessageType::class)->getForm();
        $messageForm->handleRequest($request);
        if (!$messageForm->isValid()) {
            log_error('Formバリデーションエラーです(LineIntegrationAdminController#message)');
            $searchForm = $this->formFactory->createBuilder(LineSearchType::class)->getForm();

            return $this->render(
                'ECCUBE4LineIntegration/Resource/template/admin/search.twig', array(
                'searchForm' => $searchForm->createView()
            ));
        }


        // 送信対象者を検索条件で絞り込み
        $searchData = $this->session->get('plugin.line_integration.search');

        $searchForm = $this->formFactory->createBuilder(LineSearchType::class)->getForm();
        $searchForm->handleRequest($request);

        $this->lineIntegrationRepository->setApplication($this); // 少し強引だが LineIntegrationRepository から CustomerRepo を得るため
        $targetLineIntegrations = $this->lineIntegrationRepository->getResultBySearchData($searchData);

        $lineUserIds = array();
        foreach ($targetLineIntegrations as $targetLineIntegration) {
            $lineUserIds[] = $targetLineIntegration->getLineUserId();
        }
        log_info("メッセージ送信対象 計" . count($lineUserIds) . "名", $lineUserIds);

        $inputs  = $request->request->get('line_message');
        $messages = [];
        // フォームを送信された項目順に処理
        foreach ($inputs as $key => $value) {
            if (empty($value)) {
                continue;
            }

            // テキストメッセージ
            if ($key === 'message') {
                $messages[] = [
                    "type" => "text",
                    "text" => $value,
                ];
                continue;
            }

            // 画像メッセージ
            else if ($key === 'image') {
                $textImageFileName = $value;                            // 'hoge.jpeg'
                $uploadDirUrl  = $inputs['image_dir_url'];          // '/html/upload/save_image/'
                $schemeAndHost = $request->getSchemeAndHttpHost();  // 'http://example/eccube'
                // 画像ファイルの処理
                $movedFilePath = $this->moveFileTempToSave($textImageFileName);// 'line_integration/hoge.jpeg'

                // 画像データの格納
                $uploadImageUrl = $schemeAndHost . $uploadDirUrl . $movedFilePath;
                $messages[] = [
                    "type" => "image",
                    "originalContentUrl" => $uploadImageUrl,
                    "previewImageUrl" => $uploadImageUrl,
                ];
                continue;
            }

            // スタンプ
            else if ($key === 'stamp_sticker_id') {
                continue;
            }
            else if ($key === 'stamp_package_id') {
                $packageId = $inputs['stamp_package_id'];
                $stickerId = $inputs['stamp_sticker_id'];
                $messages[] = [
                    "type" => "sticker",
                    "packageId" => $packageId,
                    "stickerId" => $stickerId,
                ];
                continue;
            }

            // 画像カルーセル
            else if ($key === 'carousel_columns') {
                $columns = [];
                foreach ((array)$value as $col) {
                    $imageFileName = $col['image_name'];                // 'hoge.jpeg'
                    $imageLinkUrl  = $col['link_url'];
                    $imageLabel    = $col['label'];
                    $uploadDirUrl  = $inputs['image_dir_url'];          // '/html/upload/save_image/'
                    $schemeAndHost = $request->getSchemeAndHttpHost();  // 'http://example/eccube'

                    if (empty($imageFileName) || empty($imageLinkUrl)) {
                        continue;
                    }
                        // 画像ファイルの処理
                        $movedFilePath = $this->moveFileTempToSave($imageFileName);
                        $uploadImageUrl = $schemeAndHost . $uploadDirUrl . $movedFilePath;
                        $column = [
                            "imageUrl" => $uploadImageUrl,
                            "action" => [
                                "type" => "uri",
                                "uri" => $imageLinkUrl,
                            ]
                        ];
                    // ラベルの付加
                    if (!empty($imageLabel)) {
                        $column["action"]["label"] = $imageLabel;
                    }
                    $columns[] = $column;
                }
                if (!empty($columns)) {
                    $messages[] = [
                        "type" => "template",
                        "altText" => "画像カルーセルはスマートフォンでのみ確認可能です。",
                        "template" => [
                            "type" => "image_carousel",
                            "columns" => $columns,
                        ]
                    ];
                    continue;
                }
            }
        }

        $postData = [
            "to" => $lineUserIds,
            "messages" => $messages
        ];

        $ch = curl_init("https://api.line.me/v2/bot/message/multicast");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charser=UTF-8',
            'Authorization: Bearer ' . $accessToken
        ));

        // デバッグ情報
        $info = curl_getinfo($ch);
        log_info(sprintf("通信内容（実行前）：%s", print_r($info, true)));

        // 通信実行
        $curlResult = curl_exec($ch);

        // デバッグ情報
        $curlResultHeader = substr($curlResult, 0, $info["header_size"]);
        log_info(sprintf("レスポンスヘッダ：%s", print_r($curlResultHeader, true)));

        // デバッグ情報
        $curlResultBody = substr($curlResult, $info["header_size"]);
        log_info(sprintf("レスポンスボディ：%s", print_r($curlResultBody, true)));

        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        // HTTPエラーハンドリング
        if (CURLE_OK !== $errno) {
            log_error(sprintf("メッセージ送信エラー(HTTP：CURL_ERRORNO:[%s] CURL_ERROR:[%s]", $errno, $error));
            throw new BadRequestHttpException($error, $errno);
        }
        log_info(sprintf("レスポンスヘッダ：%s", print_r($error, true)));
        // APIエラーハンドリング
        $resultJson = json_decode($curlResult, true);
        if (!empty($resultJson)) {
            log_error(sprintf("メッセージ送信エラー(LINE API)：[%s]", json_encode($resultJson, true)));
            throw new BadRequestHttpException($error, $errno);
        }

        //$currentDate='';
        log_info(sprintf("行：%s", __LINE__));
        $lineIntegrationHistory = new LineIntegrationHistory();
        $lineIntegrationHistory->setMessage(base64_encode($messages[0]['text']));
        $lineIntegrationHistory->setSendCount(count($targetLineIntegrations));
        $lineIntegrationHistory->setSendDate($currentDate);
        $lineIntegrationHistory->setSendImage(strpos($messages[0]['type'],"text") !== false?$messages[1]['originalContentUrl']:$messages[0]['originalContentUrl']);
        $lineIntegrationHistory->setCreateDate($currentDate);
        $lineIntegrationHistory->setDelFlg(Constant::DISABLED);
        $this->entityManager->persist($lineIntegrationHistory);
        $this->entityManager->flush();

        $this->addSuccess('メッセージを送信しました。', 'admin');
        return $this->redirectToRoute('plugin_line_message_history');
    }


    /**
     * メッセージ送信履歴の表示
     *
     * @Route("/%eccube_admin_route%/plugin_line_message_history", name="plugin_line_message_history")
     * @Template("@ECCUBE4LineIntegration/admin/history.twig")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function history(Request $request)
    {
        $filter = new \Twig\TwigFilter('get_base64_decode', function ($string) {
            return base64_decode($string);
        });
        $this->container->get('twig')->addFilter($filter);
        $lineIntegrationHistories = $this->lineIntegrationHistoryRepository->findBy(array('del_flg' => Constant::DISABLED),
            array('send_date' => 'DESC'));

        return $this->render('ECCUBE4LineIntegration/Resource/template/admin/history.twig',
            array('histories' => $lineIntegrationHistories));
    }


    /**
     * 設定画面の表示
     *
     * @Route("/%eccube_admin_route%/plugin_line_message_setting/", name="plugin_line_message_setting")
     * @Template("@ECCUBE4LineIntegration/admin/setting.twig")
     * @param Request $request
     * @param CronManageService $cronManageService
     * @return array
     */
    public function setting(Request $request, CronManageService $cronManageService)
    {
        $lineIntegrationSetting = $this->getLineIntegrationSetting();

        $settingForm = $this->formFactory
            ->createBuilder(LineSettingType::class, $lineIntegrationSetting)
            ->getForm();

        return [
            'settingForm' => $settingForm->createView(),
            'cartNotifyExecutePath' => $cronManageService->getExecutePath(),
        ];
    }


    /**
     * 設定の更新処理
     *
     * @Route("/%eccube_admin_route%/plugin_line_message_setting/commit", name="plugin_line_message_setting_commit")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function commit(Request $request)
    {
        // POST以外はエラーにする
        if ('POST' !== $request->getMethod()) {
            throw new MethodNotAllowedHttpException();
        }

        $postParameters = $request->request->get('line_setting');
        if (!array_key_exists('line_access_token', $postParameters)) {
            throw new BadRequestHttpException();
        }

        $lineAccessToken = trim($postParameters['line_access_token']);
        $lineChannelId = trim($postParameters['line_channel_id']);
        $lineChannelSecret = trim($postParameters['line_channel_secret']);
        $cartNotifyIsEnabled = $postParameters['cart_notify_is_enabled'] ?? null;
        $cartNotifyPastDayToNotify = $postParameters['cart_notify_past_day_to_notify'];
        $cartNotifyMaxCartItemCount = $postParameters['cart_notify_max_cart_item_count'];
        $cartNotifyBaseUrl = trim($postParameters['cart_notify_base_url']);
        $lineAddCancelRedirectUrl = trim($postParameters['line_add_cancel_redirect_url']);

        $cartNotifyPastDayToNotify = $cartNotifyPastDayToNotify === "" ? null : $cartNotifyPastDayToNotify;
        $cartNotifyMaxCartItemCount = $cartNotifyMaxCartItemCount === "" ? null : $cartNotifyMaxCartItemCount;

        $lineIntegrationSetting = $this->getLineIntegrationSetting();
        if (empty($lineIntegrationSetting)) {
            $lineIntegrationSetting = new LineIntegrationSetting();
        }
        $lineIntegrationSetting->setId(self::LINE_INTEGRATION_SETTING_TABLE_ID);
        $lineIntegrationSetting->setLineAccessToken($lineAccessToken);
        $lineIntegrationSetting->setLineChannelId($lineChannelId);
        $lineIntegrationSetting->setLineChannelSecret($lineChannelSecret);
        $lineIntegrationSetting->setCartNotifyIsEnabled($cartNotifyIsEnabled);
        $lineIntegrationSetting->setCartNotifyPastDayToNotify($cartNotifyPastDayToNotify);
        $lineIntegrationSetting->setCartNotifyMaxCartItemCount($cartNotifyMaxCartItemCount);
        $lineIntegrationSetting->setCartNotifyBaseUrl($cartNotifyBaseUrl);
        $lineIntegrationSetting->setLineAddCancelRedirectUrl($lineAddCancelRedirectUrl);

        $this->entityManager->persist($lineIntegrationSetting);
        $this->entityManager->flush();

        $this->addSuccess('admin.common.save_complete', 'admin');
        return $this->redirectToRoute('plugin_line_message_setting');
    }


    /**
     * LINEアクセストークンが設定済みか確認します
     *
     * @return boolean
     */
    private function isTokenSettingCompleted()
    {
        $lineIntegrationSetting = $this->getLineIntegrationSetting();
        if (empty($lineIntegrationSetting)) {
            return false;
        }

        $lineAccessToken = $lineIntegrationSetting->getLineAccessToken();
        if (empty($lineAccessToken)) {
            return false;
        }

        return true;
    }


    /**
     * LINEの設定を読み込む
     */
    private function getLineIntegrationSetting()
    {
        $lineIntegrationSetting = $this->lineIntegrationSettingRepository
            ->find(self::LINE_INTEGRATION_SETTING_TABLE_ID);
        return $lineIntegrationSetting;
    }

}
