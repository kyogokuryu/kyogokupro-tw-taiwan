<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/06
 */

namespace Plugin\KokokaraSelect\EventSubscriber;


use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\KokokaraSelect\Service\KsOrderMailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class MailEventSubscriber implements EventSubscriberInterface
{

    /** @var KsOrderMailService */
    protected $ksOrderMailService;

    public function __construct(
        KsOrderMailService $ksOrderMailService
    )
    {
        $this->ksOrderMailService = $ksOrderMailService;
    }

    /**
     * 受注メール送信
     *
     * @param EventArgs $event
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function onMailOrder(EventArgs $event)
    {

        /** @var Order $Order */
        $Order = $event->getArgument('Order');

        /** @var \Swift_Message $message */
        $message = $event->getArgument('message');

        // 選択商品情報追加
        $this->ksOrderMailService->addOrderMessage($message, $Order);

        $event->setArgument('message', $message);
    }

    /**
     * 受注メール送信初期処理
     *
     * @param EventArgs $event
     */
    public function onAdminOrderMailIndexInitialize(EventArgs $event)
    {
        /** @var FormBuilder $builder */
        $builder = $event->getArgument('builder');

        /** @var Order $Order */
        $Order = $event->getArgument('Order');

        // Event追加
        $builder
            ->get('tpl_data')
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($Order) {

                $body = $event->getData();

                if (empty($body)) return;

                // 選択商品情報の挿入
                $newBody = $this->ksOrderMailService->replaceMessage($Order, $body);
                $event->setData($newBody);
            });
    }

    public static function getSubscribedEvents()
    {
        return [
            // 受注メール送信
            EccubeEvents::MAIL_ORDER => 'onMailOrder',
            // 管理受注メールForm Init
            EccubeEvents::ADMIN_ORDER_MAIL_INDEX_INITIALIZE => 'onAdminOrderMailIndexInitialize',
        ];
    }
}
