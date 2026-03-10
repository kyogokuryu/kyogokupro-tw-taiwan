<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/06/07
 */

namespace Plugin\KokokaraSelect\Service;


use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PlgMigrationService
{

    const MIGRATION_TABLE_PREFIX = 'migration_';

    /**
     * @param array $meta
     * @param ContainerInterface $container
     * @param null $version
     * @throws MigrationException
     */
    public function pluginMigration(array $meta, ContainerInterface $container, $version = null)
    {

        $migrationFilePath = __DIR__ . '/../DoctrineMigrations';
        $connection = $container->get('database_connection');

        $pluginCode = $meta['code'];

        $config = new Configuration($connection);
        $config->setMigrationsNamespace('\Plugin\\' . $pluginCode . '\DoctrineMigrations');
        $config->setMigrationsDirectory($migrationFilePath);
        $config->registerMigrationsFromDirectory($migrationFilePath);
        $config->setMigrationsTableName(self::MIGRATION_TABLE_PREFIX . $pluginCode);

        /** @var Version $objVersion */
        foreach ($config->getMigrations() as $objVersion) {
            $versionMigration = $objVersion->getMigration();

            if ($versionMigration instanceof ContainerAwareInterface) {
                $versionMigration->setContainer($container);
            }
        }

        $migration = new Migration($config);
        $migration->migrate($version, false);
    }

    /**
     * EntityManager 取得
     *
     * @param ContainerInterface $container
     * @return EntityManagerInterface
     */
    public function getEntityManager(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        return $entityManager;
    }
}
