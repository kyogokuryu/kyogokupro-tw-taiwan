<?php

namespace Plugin\ExtraAgreeCheck;

use Eccube\Event\TemplateEvent;
use Plugin\ExtraAgreeCheck\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Contact/index.twig' => 'contactIndexTwig',
            'Contact/confirm.twig' => 'contactConfirmTwig',
            'Shopping/nonmember.twig' => 'shoppingNonmemberTwig',
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function contactIndexTwig(TemplateEvent $event)
    {
        /** @var \Plugin\ExtraAgreeCheck\Entity\Config $Config */
        $Config = $this->configRepository->get();

        if ($Config->getContactAddCheck() && $Config->getAutoInsert()) {
            $event->addSnippet('@ExtraAgreeCheck/Contact/index_snippet.twig');
            $event->setParameter('eac_check_label', $Config->getContactCheckLabel());
        }
    }

    /**
     * @param TemplateEvent $event
     */
    public function contactConfirmTwig(TemplateEvent $event)
    {
        /** @var \Plugin\ExtraAgreeCheck\Entity\Config $Config */
        $Config = $this->configRepository->get();

        if ($Config->getContactAddCheck() && $Config->getAutoInsert()) {
            $event->addSnippet('@ExtraAgreeCheck/Contact/confirm_snippet.twig');
        }
    }

    /**
     * @param TemplateEvent $event
     */
    public function shoppingNonmemberTwig(TemplateEvent $event)
    {
        /** @var \Plugin\ExtraAgreeCheck\Entity\Config $Config */
        $Config = $this->configRepository->get();

        if ($Config->getNonmemberAddCheck() && $Config->getAutoInsert()) {
            $event->addSnippet('@ExtraAgreeCheck/Shopping/nonmember_snippet.twig');
            $event->setParameter('eac_check_label', $Config->getNonmemberCheckLabel());
        }
    }
}
