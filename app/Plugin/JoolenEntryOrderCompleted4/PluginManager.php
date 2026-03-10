<?php

/*
 * Plugin Name: JoolenEntryOrderCompleted4
 *
 * Copyright(c) joolen inc. All Rights Reserved.
 *
 * https://www.joolen.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JoolenEntryOrderCompleted4;

use Doctrine\ORM\EntityManager;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    const VERSION = '1.0.0';

    /** @var string コピー元twigファイル */
    private $entryOrderCompletedTwig;

    /**
     * Enable the plugin.
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $file = new Filesystem();
        $distFile = $this->getDistFilePath($container);
        $this->entryOrderCompletedTwig = __DIR__ . '/Resource/template/default/index.twig';
        if (! $file->exists($distFile)) {
            $file->copy($this->entryOrderCompletedTwig, $distFile);
        }
    }

    /**
     * Uninstall the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws Exception
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $file = new Filesystem();
        $file->remove($this->getDistFilePath($container));

    }

    /**
     * コピー先ファイルパスを返却
     *
     * @param ContainerInterface $container
     * @return string コピー先のファイルパス
     */
    private function getDistFilePath(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        return $templateDir . '/JoolenEntryOrderCompleted4/index.twig';
    }
}
