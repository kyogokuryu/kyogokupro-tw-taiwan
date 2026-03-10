<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DroppedCartNotifierPlugin\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class CronManageService
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var string
     */
    const EXEC_COMMAND = 'bin/console dropped-cart-notifier:exec';

    public function __construct(KernelInterface $kernel = null)
    {
        $this->kernel = $kernel;
    }

    /**
     * EC-CUBEの`bin/console dropped-cart-notifier:exec`の絶対パスを取得する
     */
    public function getExecutePath()
    {
        $rootDir = realpath($this->kernel->getRootDir() . "/../..");    // EC-CUBEのルートの絶対パス
        return $rootDir . "/" . self::EXEC_COMMAND;                     // cronで実行するシェルコマンド
    }
}
