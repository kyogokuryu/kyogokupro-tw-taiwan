<?php

namespace Plugin\ECCUBE4LineIntegration\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Plugin\ECCUBE4LineIntegration\Service\NotifyMailService;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationSettingRepository;

class EntryPointCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'line-integration:dropped-cart-notifier:exec';

    /**
     * @var NotifyMailService
     */
    protected $notifyMailService;

    /**
     * @var LineIntegrationSettingRepository
     */
    protected $lineIntegrationSettingRepository;

    public function __construct(
        NotifyMailService $notifyMailService = null,
        LineIntegrationSettingRepository $lineIntegrationSettingRepository = null
    ) {
        parent::__construct();
        $this->notifyMailService = $notifyMailService;
        $this->lineIntegrationSettingRepository = $lineIntegrationSettingRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        log_info("[LineIntegration] 日次処理を開始します");

        // かご落ち設定の取得
        $config = $this->lineIntegrationSettingRepository->find(1);
        if (is_null($config)) {
            log_error("[LineIntegration] 設定を取得できませんでした");
            return;
        }
        if (!$config->getCartNotifyIsEnabled()) {
            log_warning("[LineIntegration] かご落ち機能が無効に設定されています");
            return [];
        }

        $baseUrl = $config->getCartNotifyBaseUrl();
        if (is_null($baseUrl) || $baseUrl === "") {
            log_error("[LineIntegration] かご落ち機能に関する設定項目が未設定です");
            return;
        }
        $parsedUrl = parse_url(rtrim($baseUrl, "/"));

        // https://symfony.com/doc/3.4/console/request_context.html
        $router = $this->getContainer()->get('router');
        $context = $router->getContext();
        $context->setHost($parsedUrl["host"]);
        $context->setScheme($parsedUrl["scheme"]);
        $context->setBaseUrl($parsedUrl["path"] ?? "");

        $this->notifyMailService->executeProduction();

        log_info("[LineIntegration] 日次処理を終了します");
        $io->success("日次処理を終了します");
    }
}
