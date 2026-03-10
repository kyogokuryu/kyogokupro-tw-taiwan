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

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Product;
use Plugin\SheebDlc\Entity\Config;
use Plugin\SheebDlc\PluginManager;
use Plugin\SheebDlc\Service\SaveFile\AbstractSaveFile;
use Symfony\Component\Asset\Packages;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDrive extends AbstractSaveFile
{
    const CHUNK_SIZE_BYTES = 1 * 1024 * 1024;
    static public $ENABLED_LIB_GOOGLE_API = false;

    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * @var \Google_Service_Drive
     */
    private $service;

    /**
     * @var \Google_Service_Drive_DriveFile
     */
    private $chache_file;
    
    public function __construct(EccubeConfig $eccubeConfig, Config $config, Packages $assets, $file_name = null)
    {
        parent::__construct($eccubeConfig, $config, $assets, $file_name);
        self::useGoogleApi();
        $this->client = new \Google_Client();
        $this->client->useApplicationDefaultCredentials();
        $this->client->addScope(\Google_Service_Drive::DRIVE);
        $this->client->addScope(\Google_Service_Drive::DRIVE_FILE);
        $this->client->addScope(\Google_Service_Drive::DRIVE_APPDATA);
        $this->client->addScope(\Google_Service_Drive::DRIVE_METADATA);
        $this->service = new \Google_Service_Drive($this->client);
    }

    public static function useGoogleApi()
    {
        if (self::$ENABLED_LIB_GOOGLE_API) {
            return;
        }
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        require PluginManager::getPluginRootDir() . '/lib/google-api-php-client-master/src/Google/autoload.php';
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . PluginManager::GOOGLE_CREDENTIAL_PATH);
        self::$ENABLED_LIB_GOOGLE_API = true;
    }

    public static function isExistGoogleDriveCredential()
    {
        clearstatcache();
        return is_file(PluginManager::GOOGLE_CREDENTIAL_PATH);
    }

    /**
     * ファイル保存
     * @return string
     */
    public function save(Product $Product): string 
    {
        $file_path = $this->getTempFilePath();

        /* *******************************
         *          File Upload
         * *******************************/
        // 遅延リクエスト宣言
        $this->client->setDefer(true);
        
        $file_meta = new \Google_Service_Drive_DriveFile();
        $file_meta->setName($Product->getSheebDlcOriginFileName());

        $request = $this->service->files->create($file_meta, [
            'mimeType' => $Product->getSheebDlcMime(),
            'uploadType' => 'multipart',
            'fields' => 'id, webContentLink, webViewLink'
        ]);

        $media = new \Google_Http_MediaFileUpload(
            $this->client,
            $request,
            $Product->getSheebDlcMime(),
            null,
            true,
            self::CHUNK_SIZE_BYTES
        );
        $media->setFileSize(filesize($file_path));

        // 少しずつファイルをアップロード
        $status = false;
        $handle = fopen($file_path, "rb");
        while (!$status && !feof($handle)) {
            $chunk = fread($handle, self::CHUNK_SIZE_BYTES);
            $status = $media->nextChunk($chunk);
        }

        $result = false;
        if($status != false) {
            $result = $status;
        }

        fclose($handle);
        $this->client->setDefer(false);

        /**
         * @var $result \Google_Service_Drive_DriveFile
         */
        $this->setCacheFile($result);
        
        /* *******************************
         *          Permissions
         * *******************************/
        
        $permission = new \Google_Service_Drive_Permission();
        $permission->setRole('reader');
        $permission->setType('anyone');
        $permission->setAllowFileDiscovery(true);
        $this->service->permissions->create(
            $result->getId(), $permission
        );

        $Product->setSheebDownloadContent($result->getId());
        return $result->getWebContentLink();
    }
    
    public function output(Product $Product): Response
    {
        $file_id = $Product->getSheebDownloadContent();

        /*
         * ファイルサイズを取得
         */
        $file_size = (function ($file_id) {
            try {
                /**
                 * @var $file_meta \Google_Service_Drive_DriveFile
                 */
                $file_meta = $this->service->files->get($file_id, [
                    'fields' => 'size'
                ]);
            } catch (\Google_Service_Exception $exception) {
                if ($exception->getCode() === 404) {
                    throw new InternalErrorException(trans('sheeb.dlc.downloadable.error.content_not_found'));
                } else {
                    throw $exception;
                }
            }
            
            return $file_meta->getSize();
        })($file_id);

        /**
         * リクエストを作成
         * @var $request \GuzzleHttp\Psr7\Request
         */
        $this->client->setDefer(true);
        $request = $this->service->files->get($file_id, [
            'alt' => 'media'
        ]);
        
        $response = new StreamedResponse();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Type', $Product->getSheebDlcMime());
        $response->headers->set('Content-length', $file_size);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $Product->getSheebDlcOriginFileName(),
            $this->makeContentFileNameFallback($Product->getSheebDlcOriginFileName())
        ));
        
        $response->setCallback(function () use ($request, $file_size) {
            $start = 0;
            $end = self::CHUNK_SIZE_BYTES;
            $loop = floor($file_size / self::CHUNK_SIZE_BYTES);
            $loop = $file_size % self::CHUNK_SIZE_BYTES > 0 ? $loop + 1 : $loop;

            for ($i = 0; $i < $loop; $i++) {
                $request->withHeader('Range', "bytes={$start}-{$end}");
                $chunk_file = $this->client->execute($request);
                echo $chunk_file->getBody()->getContents();
                flush();
                
                $start += self::CHUNK_SIZE_BYTES + 1;
                $end += $start + self::CHUNK_SIZE_BYTES;
            }
        });
        $response->send();
        
        $this->client->setDefer(false);

        return $response;
    }

    private function makeContentFileNameFallback($filename, $filenameFallback = '')
    {
        if ('' === $filenameFallback && (!preg_match('/^[\x20-\x7e]*$/', $filename) || false !== strpos($filename, '%'))) {
            $encoding = mb_detect_encoding($filename, null, true) ?: '8bit';

            for ($i = 0, $filenameLength = mb_strlen($filename, $encoding); $i < $filenameLength; ++$i) {
                $char = mb_substr($filename, $i, 1, $encoding);

                if ('%' === $char || \ord($char) < 32 || \ord($char) > 126) {
                    $filenameFallback .= '_';
                } else {
                    $filenameFallback .= $char;
                }
            }
        }

        return $filenameFallback;
    }
    
    /**
     * @param Product $Product
     * @return bool
     * @throws \Google_Service_Exception
     */
    public function isExistSaveFile(Product $Product): bool
    {
        $file_id = $Product->getSheebDownloadContent();
        return $this->isExistSaveFileById($file_id);
    }

    /**
     * @param $file_id
     * @return bool
     * @throws \Google_Service_Exception
     */
    public function isExistSaveFileById($file_id)
    {
        $result = false;
        if (empty($file_id)) {
            return false;
        }

        $file = null;
        try {
            $file = $this->service->files->get($file_id);
        } catch (\Google_Service_Exception $exception) {
            if ($exception->getCode() === 404) {
                $result = false;
            } else {
                throw $exception;
            }
        }

        if ($file instanceof \Google_Service_Drive_DriveFile) {
            $this->setCacheFile($file);
            $result = true;
        }

        return $result;
    }

    /**
     * ファイル削除
     * 
     * @param Product $Product
     * @return bool
     * @throws \Google_Service_Exception
     */
    public function remove(Product $Product): bool
    {
        if ($this->isExistSaveFile($Product)) {
            $file = $this->getChacheFile();
            $this->service->files->delete($file->getId());
        }

        return true;
    }

    /**
     * @param $file_id
     * @return bool
     * @throws \Google_Service_Exception
     */
    public function removeByFileId($file_id): bool
    {
        if ($this->isExistSaveFileById($file_id)) {
            $file = $this->getChacheFile();
            $this->service->files->delete($file->getId());
        }

        return true;
    }

    /**
     * 商品情報に関連づいていないファイルを全て削除
     * @param $Products
     */
    public function removeAllNotUseFiles($Products)
    {
        $file_ids = array_reduce($this->getAllNotUseFiles($Products), function ($reduced, \Google_Service_Drive_DriveFile $file) {
            $reduced[] = $file->getId();
            return $reduced;
        }, []);

        if (!empty($file_ids)) {
            foreach ($file_ids as $file_id) {
                $this->service->files->delete($file_id);
            }
        }
    }

    /**
     * 商品情報に関連づいていないファイルを取得
     * 
     * @param $Products
     * @return \Google_Service_Drive_DriveFile[]
     */
    public function getAllNotUseFiles($Products)
    {
        $result = [];
        
        $use_file_ids = array_reduce($Products, function ($reduced, Product $Product) {
            $reduced[] = $Product->getSheebDownloadContent();
            return $reduced;
        }, []);

        foreach ($this->getAllDriveFiles() as $drive_file) {
            if (!in_array($drive_file->getId(), $use_file_ids)) {
                $result[] = $drive_file;
            }
        }

        return $result;
    }

    /**
     * Google Driveに保存されている全ファイルを取得
     * @return \Google_Service_Drive_DriveFile[]
     */
    public function getAllDriveFiles()
    {
        $result = [];
        $pageToken = null;

        do {
            $parameters = [];
            if ($pageToken) {
                $parameters['pageToken'] = $pageToken;
            }
            $files = $this->service->files->listFiles($parameters);

            $result = array_merge($result, $files->getFiles());
            $pageToken = $files->getNextPageToken();
        } while ($pageToken);
        return $result;
    }

    private function getChacheFile()
    {
        return $this->chache_file;
    }
    
    private function setCacheFile(\Google_Service_Drive_DriveFile $file)
    {
        $this->chache_file = $file;
    }
}