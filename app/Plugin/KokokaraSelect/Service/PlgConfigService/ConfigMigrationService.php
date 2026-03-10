<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/06/08
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Plugin\KokokaraSelect\Service\PlgConfigService\Repository\AbstractConfigRepository;
use Psr\Container\ContainerInterface;

class ConfigMigrationService
{

    private $configData = [];

    /**
     * Config設定情報のクリア
     *
     */
    public function clearConfigData()
    {
        $this->configData = [];
    }

    /**
     * Config設定情報の追加
     *
     * @param $type
     * @param $key
     * @param $name
     * @param $default
     * @param $groupId
     * @param $sortNo
     */
    public function addConfigParam(
        $type,
        $key,
        $name,
        $default,
        $groupId,
        $sortNo
    )
    {
        $this->configData[] = [
            $type, $key, $name, $default, $groupId, $sortNo
        ];
    }

    /**
     * Config設定情報の追加
     *
     * @param $params []
     */
    public function addConfigParams($params)
    {
        $this->configData = array_merge($this->configData, $params);
    }

    /**
     * up
     *
     * @param ContainerInterface $container
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function upConfig(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $nameSpaces = explode('\\', __NAMESPACE__);;
        $plgConfigClass = sprintf("%s\%s\Entity\PlgConfig", $nameSpaces[0], $nameSpaces[1]);

        /** @var AbstractConfigRepository $ConfigRepository */
        $ConfigRepository = $entityManager->getRepository($plgConfigClass);
        $ConfigRepository->saveNewConfigs($this->configData);
    }

    /**
     * down
     *
     * @param ContainerInterface $container
     */
    public function downConfig(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $nameSpaces = explode('\\', __NAMESPACE__);;
        $plgConfigClass = sprintf("%s\%s\Entity\PlgConfig", $nameSpaces[0], $nameSpaces[1]);

        /** @var AbstractConfigRepository $ConfigRepository */
        $ConfigRepository = $entityManager->getRepository($plgConfigClass);

        foreach ($this->configData as $configDatum) {
            $key = $configDatum[1];
            $Config = $ConfigRepository->findOneBy(['configKey' => $key]);

            if (is_null($Config)) continue;

            $entityManager->remove($Config);
        }
    }

}
