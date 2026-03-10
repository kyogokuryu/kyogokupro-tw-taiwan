<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/05/05
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService;

use Plugin\KokokaraSelect\Service\PlgConfigService\Common\ConfigSettingInterface;

class ConfigHelper
{

    const TYPE_STRING = 1;

    const TYPE_CHOICE = 2;

    const TYPE_BOOL = 3;

    const TYPE_OPTIONS = 4;

    private $nameSpaces;

    private $configSetting;

    public function __construct(ConfigSettingInterface $configSetting)
    {
        $this->nameSpaces = explode('\\', __NAMESPACE__);

        $this->configSetting = $configSetting;
    }

    /**
     * NameSpaceからConfigType名を返却
     *
     * @return string
     */
    public function getConfigTypeClassName()
    {
        $nameSpaces = $this->nameSpaces;
        $configTypeClass = sprintf("%s\\%s\\Service\\PlgConfigService\\Form\\Type\\ConfigType", $nameSpaces[0], $nameSpaces[1]);
        return $configTypeClass;
    }

    /**
     * NameSpaceからPlgConfigのEntity名を返却
     *
     * @return string
     */
    public function getEntityPlgConfig()
    {
        $nameSpaces = $this->nameSpaces;
        $plgConfigClass = sprintf("%s\\%s\\Entity\\PlgConfig", $nameSpaces[0], $nameSpaces[1]);
        return $plgConfigClass;
    }

    /**
     * NameSpaceからPlgConfigOptionのEntity名を取得
     *
     * @return string
     */
    public function getEntityPlgConfigOption()
    {
        $nameSpaces = $this->nameSpaces;
        $plgConfigOptionClass = sprintf("%s\\%s\\Entity\\PlgConfigOption", $nameSpaces[0], $nameSpaces[1]);
        return $plgConfigOptionClass;
    }

    public function getSnakePlgName()
    {
        $nameSpaces = $this->nameSpaces;
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_\0', $nameSpaces[1])), '_');
    }

    /**
     * @param $key
     * @return array
     */
    public function getSettingOption($key)
    {
        $setting = $this->configSetting->getFormOptions();
        return $setting[$key];
    }

    /**
     * @param $key
     * @return bool
     */
    public function isSettingOption($key)
    {
        $settings = $this->configSetting->getFormOptions();
        return isset($settings[$key]);
    }

    /**
     * @return array
     */
    public function getSettingGroups()
    {
        return $this->configSetting->getGroups();
    }
}
