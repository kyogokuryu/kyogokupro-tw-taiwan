<?php

namespace Customize\Command;

use Plugin\MailMagazine4\Repository\MailMagazineSendHistoryRepository;
use Plugin\MailMagazine4\Service\MailMagazineService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MailMagazineScheduler extends Command
{
    /**
     * @var MailMagazineSendHistoryRepository
     */
    protected $mailMagazineSendHistoryRepository;

    /**
     * @var MailMagazineService
     */
    protected $mailMagazineService;

    /**
     *
     * @param MailMagazineSendHistoryRepository $mailMagazineSendHistoryRepository
     * @param MailMagazineService               $mailMagazineService
     */
    public function __construct(MailMagazineSendHistoryRepository $mailMagazineSendHistoryRepository, MailMagazineService $mailMagazineService)
    {
        $this->mailMagazineSendHistoryRepository = $mailMagazineSendHistoryRepository;
        $this->mailMagazineService               = $mailMagazineService;

        parent::__construct();
    }
    // コマンド名
    protected static $defaultName = 'mail-magazine:send';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var MailMagazineSendHistory[] */
        $data = $this->mailMagazineSendHistoryRepository->overScheduleList();

        if (count($data) === 0) {
            return $io->success('No Record found.');
        }

        foreach ($data as $item) {
            $this->mailMagazineService->sendrMailMagazine($item['id'], 0, 1000000);
        }

        $io->success('Sent');
    }
}
