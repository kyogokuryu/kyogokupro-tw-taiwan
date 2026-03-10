<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/03/31
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService;


use Eccube\Repository\AbstractRepository;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigInterface;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigOptionInterface;

/**
 * @version 1.4.0
 *
 * Class ConfigService
 * @package Plugin\KokokaraSelect\Service\PlgConfigService
 */
class ConfigService
{

    /** @var AbstractRepository */
    protected $configRepository;

    /** @var AbstractRepository */
    protected $configOptionRepository;

    public function __construct(
        $configRepository,
        $configOptionRepository
    )
    {
        $this->configRepository = $configRepository;
        $this->configOptionRepository = $configOptionRepository;
    }

    public function isKeyBool($key)
    {
        /** @var ConfigInterface $config */
        $config = $this->configRepository->findOneBy(['configKey' => $key]);
        return $config->isBoolValue();
    }

    public function getKeyInteger($key)
    {
        /** @var ConfigInterface $config */
        $config = $this->configRepository->findOneBy(['configKey' => $key]);
        return $config->getIntValue();
    }

    public function getKeyString($key)
    {
        /** @var ConfigInterface $config */
        $config = $this->configRepository->findOneBy(['configKey' => $key]);
        return $config->getTextValue();
    }

    public function getAllOrderGroup()
    {
        $config = $this->configRepository->findAllOrderGroup();
        return $config;
    }

    public function getOptions($key)
    {

        if($key instanceof ConfigInterface) {
            $config = $key;
        } else {
            // Keyから取り出し
            $config = $this->configRepository->findOneBy(['configKey' => $key]);
        }

        /** @var ConfigOptionInterface $configOptions */
        $configOptions = $this->configOptionRepository->findBy(
            ['PlgConfig' => $config]
        );

        return $configOptions;
    }

}
