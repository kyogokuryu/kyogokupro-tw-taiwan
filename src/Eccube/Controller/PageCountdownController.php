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

namespace Eccube\Controller;

use Eccube\Repository\PageCountdownHistoryRepository;
use Eccube\Repository\PageCountdownRepository;
use Eccube\Service\PageCountdownService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class PageCountdownController extends AbstractController
{
    protected $countdownRepository;
    protected $countdownHistoryRepository;

    protected $pageCountdownService;
    
    const ERROR_NO_USER = 1;

    public function __construct(
        PageCountdownRepository        $countdownRepository,
        PageCountdownHistoryRepository $countdownHistoryRepository,
        PageCountdownService           $pageCountdownService
    ) {
        $this->countdownRepository = $countdownRepository;
        $this->countdownHistoryRepository = $countdownHistoryRepository;
        $this->pageCountdownService = $pageCountdownService;
    }

    /**
     * @Route("/countdowns/info", name="page_countdown_info", methods={"GET"})
     *
     */
    public function info(Request $request)
    {
        $customer = $this->getUser();
        $countdownConfig = $this->countdownRepository->getCountdown();

        if (!$countdownConfig) {
            return;
        }

        $numGetReward = $this->countdownHistoryRepository->getRewardsToday($customer);
        $lastReward = $this->countdownHistoryRepository->getLastRewardByCustomer($customer);
        $info = $this->pageCountdownService->getInfoCountdownInfo($countdownConfig, $numGetReward, $lastReward);

        return $this->json([
            'active' => $info['active'] ?? false,
            'key' => $info['key'] ?? '',
            'time' => $info['time'] ?? 0,
            'second' => $info['second'] ?? 0,
            'point' => $info['point'] ?? 0,
            'next_time_get_reward' => $countdownConfig->getInterval(),
            'last_time_get_reward' => $lastReward ? $lastReward->getCreatedAt()->getTimestamp() : 0,
        ]);
    }

    /**
     * @Route("/countdowns/reward", name="page_countdown_reward", methods={"POST"})
     *
     */
    public function getReward(Request $request)
    {
        $customer = $this->getUser();
        $countdownConfig = $this->countdownRepository->getCountdown();

        if (!$countdownConfig) {
            return;
        }

        $errorCode = $customer ? null : self::ERROR_NO_USER;

        $data = $request->request->all();
        $numGetReward = $this->countdownHistoryRepository->getRewardsToday($customer);
        $lastReward = $this->countdownHistoryRepository->getLastRewardByCustomer($customer);


        try {
            $this->entityManager->beginTransaction();
            $point = $this->pageCountdownService->reward($data, $countdownConfig, $numGetReward, $customer);
            $this->entityManager->commit();
            return $this->json([
                'point' => $point,
                'userPoint' => $customer ? ($customer->getPoint()) : 0,
                'second' => $countdownConfig->getSecond(),
                'next_time_get_reward' => $countdownConfig->getInterval(),
                'last_time_get_reward' => $lastReward ? $lastReward->getCreatedAt()->getTimestamp() : 0,
                'errorCode' => $errorCode
            ]);
        } catch (\Exception $e) {
            $this->entityManager->rollBack();
            throw $e;
        }
    }
}
