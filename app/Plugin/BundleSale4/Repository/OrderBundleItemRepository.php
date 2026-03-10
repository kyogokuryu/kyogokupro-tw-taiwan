<?php
/**
 * This file is part of BundleSale4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\BundleSale4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\BundleSale4\Entity\OrderBundleItem;
use Doctrine\Common\Persistence\ManagerRegistry;

class OrderBundleItemRepository extends AbstractRepository
{
    /**
     * OrderBundleItemRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderBundleItem::class);
    }
}
