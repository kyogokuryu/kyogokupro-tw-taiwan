<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JaccsPayment;

use Eccube\Entity\Order;
use Plugin\JaccsPayment\Entity\Config;
use Plugin\JaccsPayment\Repository\ConfigRepository;
use Plugin\JaccsPayment\Repository\HistoryRepository;
use Plugin\JaccsPayment\Util\GetauthoriBatch;
use Plugin\JaccsPayment\Util\MailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JaccsPaymentCommand
 */
class JaccsPaymentCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'jaccs:payment:status-check';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var GetauthoriBatch
     */
    protected $getauthoriBatch;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var HistoryRepository
     */
    protected $historyRepository;

    /**
     * JaccsPaymentCommand constructor.
     *
     * @param ContainerInterface $container
     * @param ConfigRepository $configRepository
     * @param GetauthoriBatch $getauthoriBatch
     * @param MailService $mailService
     * @param HistoryRepository $historyRepository
     */
    public function __construct(
        ContainerInterface $container,
        ConfigRepository $configRepository,
        GetauthoriBatch $getauthoriBatch,
        MailService $mailService,
        HistoryRepository $historyRepository
    ) {
        parent::__construct();
        $this->container = $container;
        $this->configRepository = $configRepository;
        $this->getauthoriBatch = $getauthoriBatch;
        $this->mailService = $mailService;
        $this->historyRepository = $historyRepository;
    }

    protected function configure()
    {
        $this->setDescription('JaccsPayment Plugin')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, '', 30)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, '', 0);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $config Config */
        $config = $this->configRepository->get();

        if ($config && $config->getBatchType() == 1) {
            set_time_limit(intval($input->getOption('timeout')));

            try {
                $this->runProcess($config, $input, $output);
            } catch (Exception $e) {
                log_error($e->getMessage(), ['jaccs-logid' => 0]);
                throw new Exception($e->getMessage());
            }
        } else {
            $output->writeln('error: not batch type');
            log_info('error: not batch type');
            $this->mailService->sendNotBatchErrMail();
            throw new Exception('バッチのタイプをコマンドに設定してください');
        }
    }

    /**
     * @param Config $config
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function runProcess(Config $config, InputInterface $input, OutputInterface $output)
    {
        $orders = $this->getauthoriBatch->getBatchOrder(intval($input->getOption('limit')));

        if (count($orders)) {
            /** @var $order Order */
            $tIds = $this->historyRepository->getOrderTransactionIds($orders);

            $errorId = []; //返信エラーＩＤ
            $maunalId = []; //目視保留ＩＤ

            try {
                foreach ($orders as $order) {
                    $output->writeln('受注ID:'.$order->getId().'...');
                    log_info('受注ID:'.$order->getId().'...');
                    if (!array_key_exists($order->getId(), $tIds)) {
                        $output->writeln('transaction id は存在しません');
                        log_info('transaction id は存在しません');
                        continue;
                    }

                    $psKey = $this->getauthoriBatch->Getauthor($config, $order, $tIds[$order->getId()]);

                    if ($psKey == 2) {//エラー返信場合
                        $errorId[] = $order->getId();
                        $output->writeln('=>取引エラー');
                        log_info('=>取引エラー');
                    } elseif ($psKey == 3) {
                        $maunalId[] = $order->getId();
                        $output->writeln('=>保留');
                        log_info('=>保留');
                    } elseif ($psKey == 1) {
                        $output->writeln('=>取引OK');
                        log_info('=>取引OK');
                    } elseif ($psKey == 4) {
                        $output->writeln('=>取引キャンセル');
                        log_info('=>取引キャンセル');
                    } else {
                        throw new JaccsException('例外エラー');
                    }

                    if (count($errorId) || count($maunalId)) {
                        $this->mailService->sendOrderErrorMail($errorId, $maunalId);
                    }
                }

                $output->writeln('end');
                log_info('end');
            } catch (\Exception $e) {
                $output->writeln('アトディーネ通信失敗しました。'.$e->getMessage());
                log_info('アトディーネ通信失敗しました。'.$e->getMessage());
                $output->writeln('バッチの実行を中断します');
                log_info('バッチの実行を中断します');

                if (count($errorId) || count($maunalId)) {
                    $this->mailService->sendOrderErrorMail($errorId, $maunalId);
                }

                $this->mailService->sendConnErrMail();
            }
        } else {
            $output->writeln('与信審査結果確認対象なし');
            log_info('バッチの実行を中断します');
        }
    }
}
