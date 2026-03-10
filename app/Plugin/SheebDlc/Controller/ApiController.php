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
use Plugin\SheebDlc\PluginManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * 一時ファイル置き場にコンテンツを保存する
     * @Route("/%eccube_admin_route%/sheeb_dlc/content/add", name="admin_sheeb_dlc_content_add", methods={"POST"})
     */
    public function addContent(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        /**
         * @var $content UploadedFile
         */
        $content = $request->files->get('sheeb_dlc_input');

        // 不要な一時ファイルを削除
        $before_tmp_file = $request->get('admin_product')['sheeb_download_content'];
        if (!empty($before_tmp_file)) {
            $fs = new Filesystem();
            $before_tmp_file = $this->eccubeConfig['eccube_temp_image_dir'] . '/' . $before_tmp_file;
            if ($fs->exists($before_tmp_file)) {
                $fs->remove($before_tmp_file);
            }
        }

        $file = [];
        if (!empty($content)) {
            // MIMEタイプ検証
            $mimeType = $content->getClientMimeType();
            // AAAAAAAAAAAAAA
//            echo $mimeType;
            $allow_mime_types = [];
            foreach (PluginManager::ACCEPT_MIME_FOR_BACKEND as $key => $mimes) {
                foreach ($mimes as $mime) {
                    $allow_mime_types[] = "{$key}/{$mime}";
                }
            }
            if (!in_array($mimeType, $allow_mime_types)) {
                throw new UnsupportedMediaTypeHttpException($mimeType);
            }

            // 拡張子
            $extension = $content->getClientOriginalExtension();
            if (!in_array($extension, PluginManager::ACCEPT_EXTENSION_FOR_BACKEND)) {
                throw new UnsupportedMediaTypeHttpException($extension);
            }

            $filename = date('mdHis').uniqid('_').'.'.$extension;
            $content->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
            $file = [
                'file' => $filename,
                'origin_name' => $content->getClientOriginalName(),
                'mime' => $mimeType,
            ];
        }

        return $this->json(['file' => $file], 200);
    }

    /**
     * ゲスト購入の無効化
     *
     * @Route("/shopping/nonmember", name="shopping_nonmember")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToTop(Request $request)
    {
        return $this->redirectToRoute('shopping');
    }
}
