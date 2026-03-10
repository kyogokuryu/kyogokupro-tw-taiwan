<?php

declare(strict_types=1);

namespace Customize\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\EventArgs;

class AdminOrderTwigEventListener implements EventSubscriberInterface
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

    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Order/edit.twig' => 'handle'
        ];
    }

    public function handle(TemplateEvent $event): void
    {

        $count = -1;
        $regular_orders = [];
        $Order = $event->getParameter('Order');

        $order_id = $Order->getId();//$event->getArgument("id");
        /*
        if($Order->getRegularOrder()){
            $regular_order_id = $Order->getRegularOrder()->getId();

            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('o')->from('Plugin\EccubePaymentLite4\Entity\RegularOrder','o');
            $qb->where('o.id = :regular_order_id')->setParameter('regular_order_id', $regular_order_id);
            $result = $qb->getQuery()->getResult();
            $regular_order = null;
            foreach($result as $r){
                $regular_order = $r;
            }

            $qb2 = $this->entityManager->createQueryBuilder();
            $qb2->select('o')->from('Eccube\Entity\Order','o');
            $qb2->where('o.RegularOrder = :regular_order_id')->setParameter('regular_order_id', $regular_order_id);
            $qb2->andWhere('o.create_date <= :create_date')->setParameter('create_date', $Order->getCreateDate());
            $result2 = $qb2->getQuery()->getResult();
            $count = count($result2) + 1;

            $regular_orders = [];
            foreach($result2 as $k=>$v){
                $note = $v->getNote();
                if($note){
                    $note = strtr($note, ["[定期メモ]"=>"■", "時間"=>"","\n"=>" ","\r"=>" ","\n\r"=>" "]);
                    $note = trim($note);
                    
                    if(preg_match('/伝達事項(.+)/', $note, $mat)){
                      $note = $mat[1]; //strtr($note, ["伝達事項"=>"\n"]);
                      //$note = "【" . $v->getCreateDate()->format('Y/m/d') . "】\n" . $note;
                      //$note = date('Y/m/d', strtotime($v->create_date)) . $note;
                      $result2[$k]->regular_note = $note;
                      $regular_orders[] = $result2[$k];
                    }
                }
            }

        }
        */
        krsort($regular_orders);
        $event->setParameter('regular', $count);
        $event->setParameter('regular_orders', $regular_orders);
        $event->addSnippet('@admin/Order/regular.twig');
    }
}