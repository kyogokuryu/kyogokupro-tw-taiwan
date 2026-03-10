<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ECCUBE4LineIntegration;

use Eccube\Common\EccubeNav;

class LineIntegrationNav implements EccubeNav
{
    public static function getNav()
    {
        return [
            'plugin_line_integration' => [
                'name' => 'LINE管理',
                'icon' => 'fa-commenting',
                'has_child' => true,
                'children' => [
                    'plugin_line_message_search' => [
                        'name' => '配信',
                        'url' => 'plugin_line_message_search',
                    ],
                    'plugin_line_message_history' => [
                        'name' => '配信履歴',
                        'url' => 'plugin_line_message_history',
                    ],
                    'plugin_line_message_setting' => [
                        'name' => '設定',
                        'url' => 'plugin_line_message_setting',
                    ]
                ],
            ],
        ];
    }
}
