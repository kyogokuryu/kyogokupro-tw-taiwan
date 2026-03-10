<?php

declare(strict_types=1);

namespace Customize\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\EventArgs;
use Customize\Entity\PointLog;
use Customize\Entity\CLog;
use Customize\Repository\PointLogRepository;
use Customize\Repository\CLogRepository;

class AdminCustomerTwigEventListener implements EventSubscriberInterface
{

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    private $pointLogRepository;

    private $clogRepository;
    /**
     * AwardPointListener constructor.
     *
     * @param EntityManagerInterface $entityManager
     * 
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        PointLogRepository $pointLogRepository,
        CLogRepository $clogRepository
        ) 
    {
        $this->entityManager = $entityManager;
        $this->pointLogRepository = $pointLogRepository;
        $this->clogRepository = $clogRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Customer/edit.twig' => 'handle'
        ];
    }

    public function handle(TemplateEvent $event): void
    {

        $count = -1;
        $Customer = $event->getParameter('Customer');

        if ($Customer && $Customer->getId()) {
           // customer log
            $Clog = $this->clogRepository->findBy(['customer_id'=>$Customer->getId()], ['id'=>'DESC'], 1000, 0);
            //dump($Customer->getId());
            //dump($Clog);
            $countClog = count($Clog);
            $event->setParameter('countClog', $countClog);
            
            $event->setParameter('Clog', $Clog);
            $event->addSnippet('@admin/Customer/clog.twig');
            

            // PointLog
            $PointLog = $this->pointLogRepository->findBy(['customer_id'=>$Customer->getId()], ['id'=>'DESC'], 1000, 0);
            //dump($PointLog);

            $event->setParameter('PointLog', $PointLog);
            $event->addSnippet('@admin/Customer/pointlog.twig');
        }
    }
}