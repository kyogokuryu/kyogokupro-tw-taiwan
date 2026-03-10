<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/05/03
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Eccube\Repository\AbstractRepository;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigHelper;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigInterface;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigOptionInterface;

abstract class AbstractConfigRepository extends AbstractRepository
{

    private $entityClass;

    private $entityOptionsClass;

    public function __construct(ManagerRegistry $registry, $entityClass, $entityOptionsClass)
    {
        $this->entityClass = $entityClass;
        $this->entityOptionsClass = $entityOptionsClass;
        parent::__construct($registry, $entityClass);
    }

    private function getInstance()
    {
        return new $this->entityClass;
    }

    public function findAllOrderGroup()
    {
        return $this->findBy([], ['groupId' => 'asc', 'sortNo' => 'asc', 'id' => 'asc']);
    }

    /**
     * @param array $setData [TYPE, KEY, NAME, VALUE, GROUP_ID, SORT_NO]
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveNewConfigs(array $setData)
    {
        foreach ($setData as $setDatum) {

            $Config = null;
            $type = $setDatum[0];
            $key = $setDatum[1];
            $name = $setDatum[2];
            $value = $setDatum[3];
            $groupId = $setDatum[4];
            $sortNo = $setDatum[5];

            $configMethod = "get";

            switch ($type) {
                case ConfigHelper::TYPE_STRING:
                    $configMethod .= 'TextConfig';
                    break;
                case ConfigHelper::TYPE_CHOICE:
                    $configMethod .= 'IntegerConfig';
                    break;
                case ConfigHelper::TYPE_BOOL:
                    $configMethod .= 'BoolConfig';
                    break;
                case ConfigHelper::TYPE_OPTIONS:
                    $configMethod .= 'OptionsConfig';
                    break;
            }

            $Config = $this->{$configMethod}($key, $name, $value, $groupId, $sortNo);

            $this->getEntityManager()->persist($Config);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @param $key
     * @param $name
     * @param $value
     * @param $groupId
     * @param $sortNo
     * @return ConfigInterface
     */
    private function getTextConfig($key, $name, $value, $groupId, $sortNo)
    {
        /** @var ConfigInterface $Config */
        $Config = $this->getInstance();

        $Config
            ->setConfigType(ConfigHelper::TYPE_STRING)
            ->setConfigKey($key)
            ->setName($name)
            ->setTextValue($value)
            ->setIntValue(null)
            ->setBoolValue(false)
            ->setGroupId($groupId)
            ->setSortNo($sortNo);

        return $Config;
    }

    /**
     * @param $key
     * @param $name
     * @param $value
     * @param $groupId
     * @param $sortNo
     * @return ConfigInterface
     */
    private function getIntegerConfig($key, $name, $value, $groupId, $sortNo)
    {

        /** @var ConfigInterface $Config */
        $Config = $this->getInstance();

        $Config
            ->setConfigType(ConfigHelper::TYPE_CHOICE)
            ->setConfigKey($key)
            ->setName($name)
            ->setTextValue(null)
            ->setIntValue((string)$value)
            ->setBoolValue(false)
            ->setGroupId($groupId)
            ->setSortNo($sortNo);

        return $Config;
    }

    /**
     * @param $key
     * @param $name
     * @param $value
     * @param $groupId
     * @param $sortNo
     * @return ConfigInterface
     */
    private function getBoolConfig($key, $name, $value, $groupId, $sortNo)
    {
        /** @var ConfigInterface $Config */
        $Config = $this->getInstance();

        $Config
            ->setConfigType(ConfigHelper::TYPE_BOOL)
            ->setConfigKey($key)
            ->setName($name)
            ->setTextValue(null)
            ->setIntValue(null)
            ->setBoolValue($value)
            ->setGroupId($groupId)
            ->setSortNo($sortNo);

        return $Config;
    }

    /**
     * @param $key
     * @param $name
     * @param $values
     * @param $groupId
     * @param $sortNo
     * @return ConfigInterface
     */
    private function getOptionsConfig($key, $name, $values, $groupId, $sortNo)
    {
        /** @var ConfigInterface $Config */
        $Config = $this->getInstance();

        $Config
            ->setConfigType(ConfigHelper::TYPE_OPTIONS)
            ->setConfigKey($key)
            ->setName($name)
            ->setTextValue(null)
            ->setIntValue(null)
            ->setBoolValue(false)
            ->setGroupId($groupId)
            ->setSortNo($sortNo);

        foreach ($values as $value) {

            /** @var ConfigOptionInterface $ConfigOptions */
            $ConfigOptions = new $this->entityOptionsClass;
            $ConfigOptions->setValue($value);
            $ConfigOptions->setPlgConfig($Config);
            $Config->addConfigOptions($ConfigOptions);
        }

        return $Config;
    }
}
