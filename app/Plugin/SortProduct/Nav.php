<?php

namespace Plugin\SortProduct;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getNav()
    {
        return [
//            'product' => [
//                'id' => 'sort_product',
//                'name' => 'sort_product.nav',
//                'url' => 'plugin_SortProduct',
//            ],
            'product' => [
                'children' => [
                    'sort_product' => [
                        'name' => 'sort_product.nav',
                        'url' => 'plugin_SortProduct',
                    ]
                ]
            ]
        ];
    }
}
