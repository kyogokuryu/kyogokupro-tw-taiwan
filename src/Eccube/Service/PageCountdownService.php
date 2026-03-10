<?php

namespace Eccube\Service;

use Eccube\Repository\PageCountdownHistoryRepository;

class PageCountdownService
{
    protected $pageCountdownHistoryRepository;

    public function __construct(PageCountdownHistoryRepository $pageCountdownHistoryRepository)
    {
        $this->pageCountdownHistoryRepository = $pageCountdownHistoryRepository;
    }

    public function getInfoCountdownInfo($countdownConfig, $numGetReward, $lastReward)
    {
        $timeCountdownConfig = $countdownConfig->getTimes();
        $allowToGetInfo = $numGetReward < $timeCountdownConfig;

        if ($allowToGetInfo) {
            $today = new \DateTime();
            $key = $today->format('Y_m_d') . '_' . $numGetReward;
            $data['active'] = true;
            $data['key'] = $key;
            $data['time'] = $countdownConfig->getTimes();
            $data['second'] = $countdownConfig->getSecond();
            $data['point'] = $countdownConfig->getPoint();
        }

        return $data ?? 0;
    }

    public function reward($data, $countdownConfig, $numGetReward, $customer)
    {
        $keyReward = $data['key'] ?? 0;
        $isCountdownNotLogin = $data['countdown_no_login'] ?? false;
        $time = $countdownConfig->getTimes();
        $today = new \DateTime();
        $key = $today->format('Y_m_d') . '_' . $numGetReward;

        if ($numGetReward >= $time) {
            return 0;
        }
        
        if ($isCountdownNotLogin || $keyReward == $key) {
            $point = $countdownConfig->getPoint();
            $this->pageCountdownHistoryRepository->reward($customer, $countdownConfig);
        }

        return $point ?? 0;
    }
}
