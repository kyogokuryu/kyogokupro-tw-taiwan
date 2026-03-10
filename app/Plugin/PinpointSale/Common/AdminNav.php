<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/31
 */

namespace Plugin\PinpointSale\Common;


use Eccube\Common\EccubeNav;

class AdminNav implements EccubeNav
{

    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'product' => [
                'children' => [
                    'pinpoint_sale_common' => [
                        'name' => 'タイムセール設定',
                        'url' => 'admin_pinpoint_sale_common'
                    ]
                ]
            ]
        ];
    }
}
