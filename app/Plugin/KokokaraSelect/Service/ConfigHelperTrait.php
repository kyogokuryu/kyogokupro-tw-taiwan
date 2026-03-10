<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/13
 */

namespace Plugin\KokokaraSelect\Service;


use Doctrine\Common\Annotations\Annotation\Required;
use Plugin\KokokaraSelect\Config\ConfigSetting;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigService;

trait ConfigHelperTrait
{

    /** @var ConfigService */
    protected $configService;

    /**
     * @Required
     *
     * @param ConfigService $configService
     */
    public function setConfigService(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * 選択商品呼称取得
     * @return string|null
     */
    public function getKokokaraSelectName()
    {
        return $this->configService->getKeyString(ConfigSetting::SETTING_KEY_SELECT_ITEM_DEFAULT_NAME);
    }

    /**
     * 固定セット商品呼称取得
     * @return string|null
     */
    public function getKokokaraSelectDirectSelectName()
    {
        return $this->configService->getKeyString(ConfigSetting::SETTING_KEY_DIRECT_SELECT_ITEM_DEFAULT_NAME);
    }

    /**
     * デフォルトグループ名
     * @return string|null
     */
    public function getDefaultGroupName()
    {
        return $this->configService->getKeyString(ConfigSetting::SETTING_KEY_GROUP_DEFAULT_NAME);
    }
}
