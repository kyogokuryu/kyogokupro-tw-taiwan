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

namespace Plugin\SheebDlc\Service\SaveFile\Modules;

use Eccube\Entity\Product;
use Plugin\SheebDlc\Service\SaveFile\AbstractSaveFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Local extends AbstractSaveFile
{
    /**
     * EC-CUBEサーバー上に保存
     * @return string
     */
    public function save(Product $Product): string 
    {
        $file_path = $this->getFilePath();
        $fs = new Filesystem();
        $fs->rename($this->getTempFilePath(), $file_path);
        
        return $this->assets->getUrl($this->file_name, 'save_image');
    }
    
    public function output(Product $Product): Response
    {
        $mime = $Product->getSheebDlcMime();
        $save_url = $Product->getSheebDlcSaveUrl();
        
        /**
         * @var $response StreamedResponse
         */
        $this->file_name = basename($save_url);
        BinaryFileResponse::trustXSendfileTypeHeader();
        $response = new BinaryFileResponse(
            new Stream($this->getFilePath()), 200, [], false,
            null, true
        );
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $Product->getSheebDlcOriginFileName()
        );
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Type', $mime);
//        $response->headers->set('Content-length', $this->fileSize($save_url));

        return $response;
    }


    public function outputInBrowser(Product $Product): Response
    {
        $mime = $Product->getSheebDlcMime();
        $save_url = $Product->getSheebDlcSaveUrl();
        
        /**
         * @var $response StreamedResponse
         */
        $this->file_name = basename($save_url);
        BinaryFileResponse::trustXSendfileTypeHeader();
        $response = new BinaryFileResponse(
            new Stream($this->getFilePath()), 200, [], false,
            null, true
        );
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $Product->getSheebDlcOriginFileName()
        );
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-disposition', "inline; filename=" . $this->file_name);

        return $response;
    }

    public function fileSize($save_url): int
    {
        $this->file_name = basename($save_url);
        return filesize($this->getFilePath());
    }

    public function isExistSaveFile(Product $Product): bool
    {
        $result = false;

        $file_path = $this->getFilePath();
        $fs = new Filesystem();
        if ($fs->exists($file_path)) {
            $result = true;
        }
        
        return $result;
    }

    /**
     * EC-CUBEサーバー上から削除
     * @return bool
     */
    public function remove(Product $Product): bool
    {
        $file_path = $this->getFilePath();
        $fs = new Filesystem();

        clearstatcache();
        if (is_file($file_path)) {
            $fs->remove($file_path);
        }

        return true;
    }

    private function getFilePath()
    {
        return $this->eccubeConfig['eccube_save_image_dir'] . '/' . $this->file_name;
    }
}