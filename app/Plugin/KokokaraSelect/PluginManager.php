<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect;


use Eccube\Plugin\AbstractPluginManager;
use Plugin\KokokaraSelect\Service\PlgConfigService\PluginManagerTrait;
use Plugin\KokokaraSelect\Service\PlgMigrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{

    use PluginManagerTrait;

//    /**
//     * TODO:削除　デバッグ用
//     *
//     * @param array $meta
//     * @param ContainerInterface $container
//     * @throws \Doctrine\DBAL\Migrations\MigrationException
//     */
//    public function disable(array $meta, ContainerInterface $container)
//    {
//        $this->uninstall($meta, $container);
//    }
}
