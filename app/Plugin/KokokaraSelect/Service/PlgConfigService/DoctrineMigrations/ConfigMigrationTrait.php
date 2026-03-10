<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/07/13
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService\DoctrineMigrations;


use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigMigrationService;
use Plugin\KokokaraSelect\Service\PlgConfigService\Utils\ConfigServiceUtils;

/**
 * Trait ConfigMigrationTrait
 * @package Plugin\KokokaraSelect\Service\PlgConfigService\DoctrineMigrations
 *
 */
trait ConfigMigrationTrait
{

    /** @var ConfigMigrationService */
    protected $configMigrationService;

    /**
     * アップ
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function upMigration()
    {
        $this->configMigrationService = ConfigServiceUtils::getConfigMigrationService($this->container);

        $this->initConfigData($this->configMigrationService);

        $this->configMigrationService->upConfig($this->container);
    }

    /**
     * ダウン
     */
    public function downMigration()
    {
        $this->configMigrationService = ConfigServiceUtils::getConfigMigrationService($this->container);

        $this->initConfigData($this->configMigrationService);

        $this->configMigrationService->downConfig($this->container);
    }
}
