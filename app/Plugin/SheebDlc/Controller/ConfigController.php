<?php

/*
 * Project Name: ダウンロードコンテンツ販売 プラグイン for 4.0
 * Copyright(c) 2019 Kenji Nakanishi. All Rights Reserved.
 *
 * https://www.facebook.com/web.kenji.nakanishi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\SheebDlc\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Plugin\SheebDlc\Entity\Config;
use Plugin\SheebDlc\Form\Type\Admin\ConfigType;
use Plugin\SheebDlc\PluginManager;
use Plugin\SheebDlc\Repository\ConfigRepository;
use Plugin\SheebDlc\Service\SaveFile\Modules\GoogleDrive;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var Packages
     */
    protected $assets;
    
    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository, Packages $assets)
    {
        $this->configRepository = $configRepository;
        $this->assets = $assets;
    }

    /**
     * @Route("/%eccube_admin_route%/sheeb_dlc/config", name="sheeb_dlc_admin_config")
     * @Template("@SheebDlc/Admin/config.twig")
     *
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // サービスアカウントの保存
            $this->saveGoogleDriveServiceAccount($request);
            
            // データの保存
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('sheeb.dlc.admin.config.save.success', 'admin');
            return $this->redirectToRoute('sheeb_dlc_admin_config');
        }

        $has_error = false;
        $error_message = '';
        $drive_files = [];
        try {
            if ($Config->getMode() === Config::MODE_GOOGLE_DRIVE && GoogleDrive::isExistGoogleDriveCredential()) {
                /**
                 * @var $productRepository ProductRepository
                 */
                $productRepository = $this->entityManager->getRepository(Product::class);
                $Products = $productRepository->findAll();
                if (!empty($Products)) {
                    $google_drive_module = new GoogleDrive($this->eccubeConfig, $Config, $this->assets);
                    $drive_files = $google_drive_module->getAllNotUseFiles($Products);
                }
            }    
        } catch (\Exception $e) {
            $has_error = true;
            $error_message = $e->getMessage() . PHP_EOL . $e->getTraceAsString();    
        }
        
        return [
            'form' => $form->createView(),
            'drive_files' => $drive_files,
            'has_error' => $has_error,
            'error_message' => $error_message,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/sheeb_dlc/config/gdfile", name="sheeb_dlc_remove_all_google_drive_file", methods={"DELETE"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeAllGoogleDriveFile(Request $request)
    {
        $Config = $this->configRepository->get();
        $productRepository = $this->entityManager->getRepository(Product::class);
        $Products = $productRepository->findAll();
        if (!empty($Products)) {
            $google_drive_module = new GoogleDrive($this->eccubeConfig, $Config, $this->assets);
            $google_drive_module->removeAllNotUseFiles($Products);
        }
        return $this->redirectToRoute('sheeb_dlc_admin_config');
    }
    
    /**
     * @Route("/%eccube_admin_route%/sheeb_dlc/config/gdfile/{file_id}", name="sheeb_dlc_remove_google_drive_file", methods={"DELETE"})
     * 
     * @param Request $request
     * @param $file_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Google_Service_Exception
     */
    public function removeGoogleDriveFile(Request $request, $file_id)
    {
        $Config = $this->configRepository->get();
        $google_drive_module = new GoogleDrive($this->eccubeConfig, $Config, $this->assets);
        $google_drive_module->removeByFileId($file_id);
        return $this->redirectToRoute('sheeb_dlc_admin_config');
    }

    private function saveGoogleDriveServiceAccount(Request $request)
    {
        /**
         * @var $content UploadedFile
         */
        $content = $request->files->get('sheeb_dlc_google_drive_service_account');
        if (!empty($content)) {
            // MIMEタイプ検証
            $mimeType = $content->getClientMimeType();
            if ($mimeType !== 'application/json') {
                throw new UnsupportedMediaTypeHttpException();
            }

            // 拡張子
            $extension = $content->getClientOriginalExtension();
            if ($extension !== 'json') {
                throw new UnsupportedMediaTypeHttpException();
            }
            
            // 書き込み権限チェック
            $pathinfo = pathinfo(PluginManager::GOOGLE_CREDENTIAL_PATH);
            if (!is_writable($pathinfo['dirname'])) {
                throw new AccessDeniedHttpException(
                    trans('sheeb.dlc.admin.config.google_drive.service_account.error.cannot_write') .
                    '[ ' . pathinfo(PluginManager::GOOGLE_CREDENTIAL_PATH, PATHINFO_DIRNAME) . ' ]'
                );
            }

            $content->move($pathinfo['dirname'], $pathinfo['basename']);
        }
    }
}
