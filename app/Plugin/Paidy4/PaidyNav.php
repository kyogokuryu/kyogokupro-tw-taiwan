<?php

namespace Plugin\Paidy4;

use Eccube\Common\EccubeNav;

class PaidyNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
                    'paidy4_admin_payment_status' => [
                        'name' => 'paidy.admin.nav.payment_list',
                        'url' => 'paidy4_admin_payment_status',
                    ]
                ]
            ],
        ];
    }
}
