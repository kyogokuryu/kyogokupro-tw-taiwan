<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Mypage;

use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddNavEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Mypage/index.twig' => 'index',
            'Mypage/history.twig' => 'index',
            'Mypage/favorite.twig' => 'index',
            'Mypage/change.twig' => 'index',
            'Mypage/change_complete.twig' => 'index',
            'Mypage/delivery.twig' => 'index',
            'Mypage/withdraw.twig' => 'index',
            'Mypage/delivery_edit.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/edit_credit_card.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_list.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_detail.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_cycle.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_cancel.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_complete.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_next_delivery_date.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_product_quantity.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_resume.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_shipping.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_skip.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_suspend.twig' => 'index',

            '@GmoPaymentGateway4/mypage_card.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $event)
    {
        $event->addSnippet('@EccubePaymentLite4/default/Mypage/Nav/nav_credit_card.twig');
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        if ($Config->getRegular()) {
            $event->addSnippet('@EccubePaymentLite4/default/Mypage/Nav/nav_regular_index.twig');
        }
    }
}
