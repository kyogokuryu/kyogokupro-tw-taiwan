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

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentCommentEvent implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
			'@admin/Setting/Shop/payment_edit.twig' => 'onAdminPaymentEdit',
			'@admin/Order/edit.twig' => 'onAdminOrderEdit',
            'Shopping/index.twig' => 'onShopping',
            'Shopping/confirm.twig' => 'onShoppingConfirm',
        ];
    }

    public function onAdminPaymentEdit(TemplateEvent $event)
    {
        $event->addSnippet('@PaymentComment/admin/add_payment_edit.twig');
    }
	
    public function onAdminOrderEdit(TemplateEvent $event)
    {
        $event->addSnippet('@PaymentComment/admin/add_order_edit.twig');
    }
	
    public function onShopping(TemplateEvent $event)
    {
        $event->addSnippet('@PaymentComment/front/add_shopping_index.twig');
    }

    public function onShoppingConfirm(TemplateEvent $event)
    {
        $event->addSnippet('@PaymentComment/front/add_shopping_confirm.twig');
    }
}
