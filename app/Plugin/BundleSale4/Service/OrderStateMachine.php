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

namespace Plugin\BundleSale4\Service;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\BundleSale4\Service\PurchaseFlow\Processor\BundleItemStockReduceProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Symfony\Component\Workflow\Event\Event;

class OrderStateMachine implements EventSubscriberInterface
{

    private $stockReduceProcessor;

    public function __construct(BundleItemStockReduceProcessor $stockReduceProcessor)
    {
        $this->stockReduceProcessor = $stockReduceProcessor;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.order.transition.cancel' => [['rollbackStock']],
            'workflow.order.transition.back_to_in_progress' => [['commitStock']],
        ];
    }

    /**
     * 在庫を減らす.
     *
     * @param Event $event
     *
     * @throws PurchaseFlow\PurchaseException
     */
    public function commitStock(Event $event)
    {
        /* @var Order $Order */
        $Order = $event->getSubject()->getOrder();
        $this->stockReduceProcessor->prepare($Order, new PurchaseContext());
    }

    /**
     * 在庫を戻す.
     *
     * @param Event $event
     */
    public function rollbackStock(Event $event)
    {
        /* @var Order $Order */
        $Order = $event->getSubject()->getOrder();
        $this->stockReduceProcessor->rollback($Order, new PurchaseContext());
    }

}
