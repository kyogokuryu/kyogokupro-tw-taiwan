<?php

namespace Plugin\Collection;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'content' => [
                'children' => [
                    'collection' => ['name' => 'collection.admin.collection.title', 'url' => 'admin_collection'],
                    'collection_edit' => ['name' => 'collection.admin.collection.edit.title', 'url' => 'admin_collection_new']
                ],
            ],];
    }
}
