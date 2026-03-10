<?php

namespace Plugin\Paidy4;

use Eccube\Common\EccubeConfig;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\PaymentRepository;
use Plugin\Paidy4\Repository\ConfigRepository;
use Plugin\Paidy4\Service\Method\Paidy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaidyEvent implements EventSubscriberInterface
{
    /**
     * @var OrderRepository
     */
    protected $eccubeConfig;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * PaidyEvent
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->Config = $this->configRepository->get();
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
            'Shopping/confirm.twig' => 'confirm',
        ];
    }

    public function index(TemplateEvent $event)
    {
        $Payment = $this->paymentRepository->findOneBy(['method_class' => Paidy::class]);

        if ($Payment && $this->Config['description_disp'] == 1) {
            $parameters = $event->getParameters();
            $parameters['paidy_payment_id'] = $Payment->getId();
            $parameters['plugin_config'] = $this->Config;
            $parameters['paidy_customer_link'] = $this->eccubeConfig['paidy']['paidy_customer_link'];

            $event->setParameters($parameters);

            $event->addSnippet('@Paidy4/default/Shopping/paidy_shopping_info.twig');
        }
    }

    public function confirm(TemplateEvent $event)
    {
        $event->addSnippet('@Paidy4/default/Shopping/confirm_button.twig');
    }
}
