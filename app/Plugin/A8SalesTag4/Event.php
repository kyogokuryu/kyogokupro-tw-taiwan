<?php

namespace Plugin\A8SalesTag4;

use Eccube\Event\EccubeEvents;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\A8SalesTag4\Repository\ConfigRepository;
use Plugin\A8SalesTag4\Repository\TrackingRepository;

class Event implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    protected $ConfigRepository;

    /**
     * Event constructor.
     * 
     * @param ConfigRepository $ConfigRepository
     * @param TrackingRepository $TrackingRepository
     */
    public function __construct(ConfigRepository $ConfigRepository, TrackingRepository $TrackingRepository)
    {
        $this->ConfigRepository = $ConfigRepository;
        $this->TrackingRepository = $TrackingRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
	    return [
		    'Shopping/complete.twig' => 'onShoppingCompleteTwig',
            '@A8SalesTag4/default/Shopping/sales.twig' => 'onIncludeShoppingSalesTwig',
	    ];
    }
    
    /**
     * CVタグのtwigが呼ばれたとき（自動・手動設置の両方）
     */
    public function onIncludeShoppingSalesTwig(TemplateEvent $event){
        $config = $this->ConfigRepository->get(); 

	    if (is_null($config)) {
		    return;
	    }

	    $parameters = $event->getParameters();
        $parameters['eid'] = $config->getEid();
        $pids = $config->getPids();
        $parameters['pids'] = empty($pids) ? $this->TrackingRepository->findPidsBy($parameters['eid']) : $pids;
        if(empty($parameters['pids'])){
            $parameters['pids'] = $parameters['eid']; 
        }
        $event->setParameters($parameters); 

	    $this->TrackingRepository->completed();

    }

    /**
     * EC-Cubeのデフォルトでの注文完了画面が呼ばれたとき
     */
    public function onShoppingCompleteTwig(TemplateEvent $event)
    {
	    $twig = '@A8SalesTag4/default/Shopping/sales.twig';
	    $event->addSnippet($twig);

        $this->onIncludeShoppingSalesTwig($event);
    } 

}
