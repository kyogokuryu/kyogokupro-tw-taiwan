<?php

namespace Plugin\EccubePaymentLite4\Controller\Command;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Repository\CustomerRepository;
use Plugin\EccubePaymentLite4\Service\ChangeCardNotificationMailService;
use Plugin\EccubePaymentLite4\Service\GetCustomerForSendChangeCardMailService;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendChangeCardMailCommand extends Command
{
    protected static $defaultName = 'gmo_epsilon_4:regular:send_change_card_mail';
    /**
     * @var ChangeCardNotificationMailService
     */
    private $changeCardNotificationMailService;
    /**
     * @var SymfonyStyle
     */
    private $io;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;
    /**
     * @var GetCustomerForSendChangeCardMailService
     */
    private $getCustomerForSendChangeCardMailService;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        $name = null,
        ChangeCardNotificationMailService $changeCardNotificationMailService,
        IsActiveRegularService $isActiveRegularService,
        GetCustomerForSendChangeCardMailService $getCustomerForSendChangeCardMailService,
        CustomerRepository $customerRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($name);
        $this->changeCardNotificationMailService = $changeCardNotificationMailService;
        $this->isActiveRegularService = $isActiveRegularService;
        $this->getCustomerForSendChangeCardMailService = $getCustomerForSendChangeCardMailService;
        $this->customerRepository = $customerRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send a notification email to members who are notified that their credit card has expired.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->isActiveRegularService->isActive()) {
            $this->io->text('=== Regular setting is not Active. ===');

            return;
        }
        $this->io->text('=== SendChangeCardMailCommand start. ===');

        $customerIds = $this->getCustomerForSendChangeCardMailService->get();
        foreach ($customerIds as $ids) {
            foreach ($ids as $id) {
                /** @var Customer $Customer */
                $Customer = $this->customerRepository->find($id);
                $this->changeCardNotificationMailService->sendMail($Customer);
                $this->io->text('=== Customer id: '.$Customer->getId().' send. ===');
                $Customer->setCardChangeRequestMailSendDate(new \DateTime());
                $this->entityManager->persist($Customer);
            }
        }
        $this->entityManager->flush();

        $this->io->text('=== SendChangeCardMailCommand end. ===');
    }
}
