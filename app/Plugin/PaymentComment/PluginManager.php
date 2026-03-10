<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\PaymentComment;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{

    public function install(array $meta, ContainerInterface $container)
    {
	}

    public function enable(array $meta, ContainerInterface $container)
    {
    }

    public function disable(array $meta = null, ContainerInterface $container)
    {
    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
    }

}
