<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DroppedCartNotifierPlugin\Controller\Admin;

use Eccube\Repository\BaseInfoRepository;
use Plugin\DroppedCartNotifierPlugin\Service\NotifyMailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\DroppedCartNotifierPlugin\Repository\DroppedCartNotifierConfigRepository;
use Plugin\DroppedCartNotifierPlugin\Form\Type\Admin\DroppedCartNotifierConfigType;
use Plugin\DroppedCartNotifierPlugin\Service\CronManageService;
use Plugin\DroppedCartNotifierPlugin\Service\RecommendPluginIntegration;

class IndexController extends \Eccube\Controller\AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var CronManageService
     */
    protected $cronManageService;

    /**
     * @var RecommendPluginIntegration
     */
    protected $recommendPluginIntegration;

    /**
     * @var DroppedCartNotifierConfigRepository
     */
    protected $configRepository;

    public function __construct(
        EntityManagerInterface $em = null,
        CronManageService $cronManageService = null,
        RecommendPluginIntegration $recommendPluginIntegration = null,
        DroppedCartNotifierConfigRepository $configRepository = null
    ) {
        $this->entityManager = $em;
        $this->cronManageService = $cronManageService;
        $this->recommendPluginIntegration = $recommendPluginIntegration;
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/dropped_cart_notifier", name="dropped_cart_notifier_plugin_admin_config")
     * @Template("@DroppedCartNotifierPlugin/admin/index.twig")
     *
     * @param Request $request
     * @param DroppedCartNotifierConfigRepository $configRepository
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function index(Request $request)
    {
        $config = $this->configRepository->get();
        $isConfigEmpty = is_null($config);

        $form = $this->createForm(DroppedCartNotifierConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config = $form->getData();
            $this->entityManager->persist($config);
            $this->entityManager->flush();

            log_info('[DroppedCartNotifier] 設定を更新しました');
            $this->addSuccess('設定を更新しました', 'admin');

            return $this->redirectToRoute('dropped_cart_notifier_plugin_admin_config');
        }

        return [
            'isConfigEmpty' => $isConfigEmpty,
            'isRecommendPluginInstalled' => $this->recommendPluginIntegration->isEnabledRecommendedPlugin(),
            'form' => $form->createView(),
            'executePath' => $this->cronManageService->getExecutePath(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/dropped_cart_notifier/initialize", name="dropped_cart_notifier_initialize")
     * @Template("@DroppedCartNotifierPlugin/admin/initialize.twig")
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function initialize(Request $request)
    {
        $config = $this->configRepository->get();
        $isConfigEmpty = is_null($config);
        if ($isConfigEmpty) {
            $config = $this->configRepository->createDefaultConfig();
        }

        $form = $this->createForm(DroppedCartNotifierConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config = $form->getData();
            $this->entityManager->persist($config);
            $this->entityManager->flush();

            log_info('[DroppedCartNotifier] 初期設定を完了しました。メールを自動送信するには、cronを手動で設定してください');
            $this->addSuccess('初期設定を完了しました。メールを自動送信するには、cronを手動で設定する必要があります。詳しくはドキュメントをご参照ください', 'admin');

            return $this->redirectToRoute('dropped_cart_notifier_plugin_admin_config');
        }

        return [
            'isConfigEmpty' => $isConfigEmpty,
            'isRecommendPluginInstalled' => $this->recommendPluginIntegration->isEnabledRecommendedPlugin(),
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/dropped_cart_notifier/check_confirm", name="dropped_cart_notifier_check_confirm", methods={"GET"})
     * @Template("@DroppedCartNotifierPlugin/admin/check.twig")
     *
     * @param Request $request
     * @param BaseInfoRepository $baseInfoRepository
     * @param NotifyMailService $notifyMailService
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function notifyCheckConfirm(
        Request $request,
        BaseInfoRepository $baseInfoRepository,
        NotifyMailService $notifyMailService
    ) {
        $config = $this->configRepository->get();
        if (is_null($config)) {
            $this->addError('初期設定が行われていないため、動作確認を行えません。', 'admin');

            return $this->redirectToRoute('dropped_cart_notifier_plugin_admin_config');
        }

        $baseInfo = $baseInfoRepository->get();
        $targetCustomers = $notifyMailService->getNotifyTargetCustomers($config->getPastDayToNotify());

        return [
            'adminMail' => $baseInfo->getEmail01(),
            'targetCount' => count($targetCustomers),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/dropped_cart_notifier/check_execute", name="dropped_cart_notifier_check_execute", methods={"POST"})
     *
     * @param Request $request
     * @param NotifyMailService $notifyMailService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function notifyCheck(Request $request, NotifyMailService $notifyMailService)
    {
        $this->isTokenValid();

        $config = $this->configRepository->get();
        if (is_null($config)) {
            $this->addError('初期設定が行われていないため、動作確認を行えません。', 'admin');

            return $this->redirectToRoute('dropped_cart_notifier_plugin_admin_config');
        }

        log_info('[DroppedCartNotifier] かご落ちメール動作確認を開始します');

        $notifyMailService->executeOperationCheck($config->getIsSendReportMail());

        log_info('[DroppedCartNotifier] かご落ちメール動作確認を完了しました');
        $this->addSuccess('かご落ちメール動作確認を完了しました', 'admin');

        return $this->redirectToRoute('dropped_cart_notifier_plugin_admin_config');
    }

}
