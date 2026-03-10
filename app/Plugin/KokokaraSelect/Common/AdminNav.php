<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/07/24
 */

namespace Plugin\KokokaraSelect\Common;


use Eccube\Common\EccubeNav;

class AdminNav implements EccubeNav
{

    public static function getNav()
    {
        return [
            'product' => [
                'children' => [
                    'admin_kokokara_select_root' => [
                        'name' => 'kokokara_select.csv.root.title',
                        'children' => [
                            'admin_kokokara_select' => [
                                'name' => 'kokokara_select.csv.title',
                                'url' => 'admin_kokokara_select_csv',
                            ],
                            'admin_kokokara_select_direct' => [
                                'name' => 'kokokara_select.csv.direct.title',
                                'url' => 'admin_kokokara_select_direct_csv',
                            ],
                        ]
                    ],
                ]
            ]
        ];
    }
}
