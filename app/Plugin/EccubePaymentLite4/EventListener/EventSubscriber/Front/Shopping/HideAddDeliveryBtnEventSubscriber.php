<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class HideAddDeliveryBtnEventSubscriber implements EventSubscriberInterface
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
            'Shopping/index.twig' => 'index',
            'Shopping/confirm.twig' => 'confirm',
        ];
    }

    public function index(TemplateEvent $templateEvent)
    {
        /** @var Order $Order */
        $Order = $templateEvent->getParameter('Order');

        $OrderItems = $Order->getOrderItems();
        $ProductId = $OrderItems[0]->getProduct()->getId();

        $Config = $this->configRepository->find(1);
        $prime_flg = $ProductId == $Config->getPrimeProductId();
        //$prime_flg = false;

        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();
        /** @var SaleType $SaleType */
        $SaleType = $Shipping->getDelivery()->getSaleType();
        if ($SaleType->getName() === '定期商品') {
            $templateEvent->addSnippet('@EccubePaymentLite4/default/Shopping/hide_add_delivery_btn.twig');
            $templateEvent->setParameter('prime_flg', $prime_flg);
        }
    }


    public function confirm(TemplateEvent $templateEvent)
    {
        /** @var Order $Order */
        $Order = $templateEvent->getParameter('Order');

        $OrderItems = $Order->getOrderItems();
        $ProductId = $OrderItems[0]->getProduct()->getId();

        $Config = $this->configRepository->find(1);
        $prime_flg = $ProductId == $Config->getPrimeProductId();
        //$prime_flg = false;

        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();
        /** @var SaleType $SaleType */
        $SaleType = $Shipping->getDelivery()->getSaleType();
        if ($SaleType->getName() === '定期商品') {
            $templateEvent->addSnippet('@EccubePaymentLite4/default/Shopping/hide_add_delivery_btn_confirm.twig');
            $templateEvent->setParameter('prime_flg', $prime_flg);
        }
    }

}
