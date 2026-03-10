<?php

namespace Plugin\DroppedCartNotifierPlugin\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Router;

use Plugin\DroppedCartNotifierPlugin\Service\NotifyMailService;
use Plugin\DroppedCartNotifierPlugin\Repository\DroppedCartNotifierConfigRepository;

class EntryPointCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'dropped-cart-notifier:exec';

    /**
     * @var NotifyMailService
     */
    protected $notifyMailService;

    /**
     * @var DroppedCartNotifierConfigRepository
     */
    protected $droppedCartNotifierConfigRepository;

    public function __construct(
        NotifyMailService $notifyMailService = null,
        DroppedCartNotifierConfigRepository $droppedCartNotifierConfigRepository = null
    ) {
        parent::__construct();
        $this->notifyMailService = $notifyMailService;
        $this->droppedCartNotifierConfigRepository = $droppedCartNotifierConfigRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        log_info("[DroppedCartNotifier] 日次処理を開始します");

        // かご落ち設定の取得
        $config = $this->droppedCartNotifierConfigRepository->get();
        if (is_null($config)) {
            log_warning("[DroppedCartNotifier] 設定を取得できませんでした");
            return;
        }
        $isSendReportMail = $config->getIsSendReportMail();
        $parsedUrl = parse_url(rtrim($config->getBaseUrl(), "/"));

        // https://symfony.com/doc/3.4/console/request_context.html
        /** @var Router $router */
        $router = $this->getContainer()->get('router');
        $context = $router->getContext();
        $context->setHost($parsedUrl["host"]);
        $context->setScheme($parsedUrl["scheme"]);
        $context->setBaseUrl($parsedUrl["path"] ?? "");

        $this->notifyMailService->executeProduction($isSendReportMail);

        log_info("[DroppedCartNotifier] 日次処理を終了します");
        $io->success("日次処理を終了します");
    }
}
