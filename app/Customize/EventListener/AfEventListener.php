<?php

namespace Customize\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\EventArgs;

class AfEventListener implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * AwardPointListener constructor.
     *
     * @param EntityManagerInterface $entityManager
     * 
     */
    public function __construct(EntityManagerInterface $entityManager) 
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
	    return [
		    'Shopping/complete.twig' => 'onShoppingCompleteTwig',
            '@JaccsPayment/default/jaccs_examination_complete.twig' => 'onShoppingCompleteTwig',
	    ];
    }
    

    /**
     * EC-Cubeのデフォルトでの注文完了画面が呼ばれたとき
     */
    public function onShoppingCompleteTwig(TemplateEvent $event)
    {
        $Order = $event->getParameter('Order');

        if (!$Order) {
            return;
        }

        $orderId = $Order->getId();
        $Customer = $Order->getCustomer();

        $customerId = $Customer ? $Customer->getId() : null;

        log_info(sprintf(
            '[CUSTOM_LOG] AfEventListener.onShoppingCompleteTwig order_id=%s customer_id=%s',
            $orderId,
            $customerId ?? 'guest'
        ));

        $this->send_af_param(
            $orderId,
            $customerId,
            $Order->getOrderItems()
        );
    }


    /**
     *
     */
    function send_af_param($order_id, $user_id, $OrderItems){
        $abm = isset($_COOKIE["afbcookie"]) ? $_COOKIE["afbcookie"] : null;
        log_info(sprintf('[CUSTOM_LOG] AfEventListener.send_af_param abm = %s', $abm));

        if($abm){
            $items = [];
            foreach($OrderItems as $k=>$v){
                $Product = $v->getProduct();
                if($Product){
                    $tmp = [];
                    $tmp[] = $Product->getId();
                    $tmp[] = (int)$v->getQuantity();
                    $tmp[] = (int)$v->getPrice();
                    $items[] = implode('.', $tmp);
                }
            }

            // https://t.afi-b.com/commit/i14079k/【サイトユーザー識別ID】/【商品コード】.【購入個数】.【商品単価】:【商品コード】.【購入個数】.【商品単価】・・・&abm=【保存した値】

            $url = sprintf('https://t.afi-b.com/commit/i14079k/%s/%s&abm=%s', $order_id, implode(":",$items), $abm);
            log_info(sprintf('[CUSTOM_LOG] AfEventListener.send_af_param url = %s', $url));
            file($url);

            // Cooki
            if($_SERVER['APP_DEBUG']){
                setcookie('afbcookie', $abm, time() - 1, '/', 'xs564860.xsrv.jp');
            }else{
                setcookie('afbcookie', $abm, time() - 1, '/', 'kyogokupro.com', true, true);
            }
        }else{
            $items = [];
            foreach($OrderItems as $k=>$v){
                $Product = $v->getProduct();
                if($Product){
                    $tmp = [];
                    $tmp[] = $Product->getId();
                    $tmp[] = (int)$v->getQuantity();
                    $tmp[] = (int)$v->getPrice();
                    $items[] = implode('.', $tmp);
                }
            }
            $url = sprintf('https://t.afi-b.com/commit/i14079k/%s/%s&abm=%s', $order_id, implode(":",$items), $abm);
            log_info(sprintf('[DEBUG_LOG] AfEventListener.send_af_param dry url = %s', $url));
        
        }
    }

}
