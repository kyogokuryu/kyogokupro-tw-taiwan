<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/07/13
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService;


use Doctrine\DBAL\Migrations\MigrationException;
use Plugin\KokokaraSelect\Service\PlgConfigService\Utils\ConfigServiceUtils;
use Plugin\KokokaraSelect\Service\PlgMigrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait PluginManagerTrait
{

    /**
     * アンインストール
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws MigrationException
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        /** @var PlgMigrationService $plgMigrationService */
        $plgMigrationService = ConfigServiceUtils::getPlgMigrationService($container);
        $plgMigrationService->pluginMigration($meta, $container, 0);
    }

    /**
     * 有効化
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws MigrationException
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        /** @var PlgMigrationService $plgMigrationService */
        $plgMigrationService = ConfigServiceUtils::getPlgMigrationService($container);
        $plgMigrationService->pluginMigration($meta, $container);
    }

    /**
     * アップデート
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws MigrationException
     */
    public function update(array $meta, ContainerInterface $container)
    {
        /** @var PlgMigrationService $plgMigrationService */
        $plgMigrationService = ConfigServiceUtils::getPlgMigrationService($container);
        $plgMigrationService->pluginMigration($meta, $container);
    }
}
