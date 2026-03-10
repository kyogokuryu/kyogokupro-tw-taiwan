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

namespace Plugin\SheebDlc\Service\SaveFile;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Product;
use Plugin\SheebDlc\Entity\Config;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractSaveFile
{
    /**
     * @var EccubeConfig 
     */
    protected $eccubeConfig;

    /**
     * @var Config 
     */
    protected $config;

    /**
     * @var string
     */
    protected $file_name;

    /**
     * @var Packages
     */
    protected $assets;
    
    /**
     * @var string
     */
    protected $exist_temp_file_path;
    
    public function __construct(EccubeConfig $eccubeConfig, Config $config, Packages $assets, $file_name = null)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->config = $config;
        $this->assets = $assets;
        $this->file_name = $file_name;
        $this->exist_temp_file_path = $this->eccubeConfig['eccube_temp_image_dir'] . '/' . $file_name;
    }

    public function throwIfNotExistTempFile()
    {
        if (!is_file($this->exist_temp_file_path)) {
            throw new BadRequestHttpException(trans('sheeb.dlc.admin.save.content_is_no_exist'));
        }
    }

    public function getTempFilePath()
    {
        return $this->exist_temp_file_path;
    }

    abstract function isExistSaveFile(Product $Product): bool;
    
    abstract function save(Product $Product): string;
    
    abstract function output(Product $Product): Response;

    abstract function remove(Product $Product): bool;
}
