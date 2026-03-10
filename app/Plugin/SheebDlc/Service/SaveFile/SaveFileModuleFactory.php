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
use Plugin\SheebDlc\Entity\Config;
use Plugin\SheebDlc\Service\SaveFile\Modules\GoogleDrive;
use Plugin\SheebDlc\Service\SaveFile\Modules\Local;
use Symfony\Component\Asset\Packages;

class SaveFileModuleFactory
{
    /**
     * @param EccubeConfig $eccubeConfig
     * @param Config $config
     * @param Packages $assets
     * @param null $file_name
     * @return GoogleDrive|Local
     * @throws \Exception
     */
    static function get(EccubeConfig $eccubeConfig, Config $config, Packages $assets, $file_name = null)
    {
        switch ($config->getMode()) {
            case Config::MODE_LOCAL:
                $instance = new Local($eccubeConfig, $config, $assets, $file_name);
                break;
            case Config::MODE_GOOGLE_DRIVE:
                $instance = new GoogleDrive($eccubeConfig, $config, $assets, $file_name);
                break;
            default:
                throw new \Exception('Mode設定異常');
                break;
        }
        return $instance;
    }
}