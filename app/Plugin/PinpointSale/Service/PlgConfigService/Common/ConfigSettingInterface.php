<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/05/05
 */

namespace Plugin\PinpointSale\Service\PlgConfigService\Common;


interface ConfigSettingInterface
{

    /**
     * @return array
     */
    public function getGroups();

    /**
     * @return array
     */
    public function getFormOptions();
}
