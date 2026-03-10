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

namespace Plugin\BundleSale4\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Plugin\BundleSale4\Entity\BundleItem;
use Plugin\BundleSale4\Request\Context;


class BundleItemSubscriber implements EventSubscriber
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Context
     */
    private $requestContext;

    public function __construct(
        EntityManagerInterface $entityManager,
        Context $requestContext
    )
    {
        $this->entityManager = $entityManager;
        $this->requestContext = $requestContext;
    }

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postUpdate
        ];
    }

    /**
     * 商品を一括で非公開や廃止にしようとしたとき、セット商品に含まれている商品が見つかったらエラー発生させる。
     *
     * @param LifecycleEventArgs $args
     * @throws Exception
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Product) {
            return;
        }

        //向井修正分　何故かわからないがセット商品扱いになってしまうので一旦オフ
        // if ($this->requestContext->isRoute("admin_product_bulk_product_status")) {
        //     if ($entity->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
        //         $ProductClasses = $entity->getProductClasses()->toArray();

        //         $BundleItemRepository = $this->entityManager->getRepository(BundleItem::class);
        //         $BundleItems = $BundleItemRepository->countByProductClass($ProductClasses);

        //         if ($BundleItems > 0) {
        //             throw new \Exception(trans('plugin.bundle_sale.admin.product.update_error', ["%product%" => $entity->getName(), "%product_status%" => $entity->getStatus()]));
        //         }
        //     }
        // }
    }
}
