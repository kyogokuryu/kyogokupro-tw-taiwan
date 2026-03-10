<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/07/13
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService\Utils;


use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigServiceUtils
{

    private static function getPluginNameByNamespace()
    {
        $nameSpaces = explode('\\', __NAMESPACE__);
        return $nameSpaces[1];
    }

    public static function getPlgMigrationService(ContainerInterface $container)
    {

        $plgName = self::getPluginNameByNamespace();

        return $container->get(
            Container::underscore($plgName) . '.plg_migration');
    }

    public static function getConfigMigrationService(ContainerInterface $container)
    {

        $plgName = self::getPluginNameByNamespace();

        return $container->get(
            Container::underscore($plgName) . '.config_migration');
    }
}
