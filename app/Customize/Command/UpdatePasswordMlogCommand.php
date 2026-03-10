<?php

namespace Customize\Command;

use DateTime;
use Doctrine\ORM\EntityManager;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Repository\BaseInfoRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UpdatePasswordMlogCommand extends Command {
    protected static $defaultName = "update-passoword-mlog";

    protected $baseInfoRepository;

    protected $entityManager;

    protected $eccubeConfig;

    protected $encoderFactory;

    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        EntityManager $entityManager,
        EccubeConfig $eccubeConfig,
        EncoderFactoryInterface $encoderFactory
    )
    {
        $this->baseInfoRepository = $baseInfoRepository;
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->encoderFactory = $encoderFactory;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('password',InputArgument::REQUIRED,'The password product cost page');   
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new DateTime();
        try {
            $customer = new Customer();
            $encoder = $this->encoderFactory->getEncoder($customer);
            $password = $encoder->encodePassword($input->getArgument('password'), null);
            $this->baseInfoRepository->get()->setMlogPassword($password);
            $this->entityManager->flush();

            $timestamp = strtotime($now->format("H:i:s"));
            $output->write("$timestamp - Update password product cost page successfully\n");
        } catch (\Throwable $th) {
            $timestamp = strtotime($now->format("H:i:s"));
            $output->write("$timestamp - Update password product cost page failed\n $th");
        }
        
    }
}