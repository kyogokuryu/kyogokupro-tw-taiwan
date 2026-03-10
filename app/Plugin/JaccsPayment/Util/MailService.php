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

namespace Plugin\JaccsPayment\Util;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\baseInfo;
use Eccube\Repository\BaseInfoRepository;
use Plugin\JaccsPayment\Repository\ConfigRepository;

class MailService
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var baseInfo
     */
    protected $baseInfo;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * MailService constructor.
     *
     * @param \Swift_Mailer $mailer
     * @param EccubeConfig $eccubeConfig
     * @param ConfigRepository $configRepository
     * @param BaseInfoRepository $baseInfoRepository
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __construct(
        \Swift_Mailer $mailer,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        BaseInfoRepository $baseInfoRepository
    ) {
        $this->mailer = $mailer;
        $this->eccubeConfig = $eccubeConfig;
        $this->baseInfo = $baseInfoRepository->get();
        $this->configRepository = $configRepository;
    }

    /**
     * @param $title
     * @param $text
     *
     * @return int
     */
    protected function sendMail($title, $text)
    {
        $message = (new \Swift_Message())
            ->setSubject('['.$this->baseInfo->getShopName().'] '.$title)
            ->setFrom([$this->baseInfo->getEmail01() => $this->baseInfo->getShopName()])
            ->setTo([$this->configRepository->get()->getEmail()])
            ->setReturnPath($this->baseInfo->getEmail04())
            ->setBody($text);

        $count = $this->mailer->send($message, $failures);

        return $count;
    }

    public function sendNotBatchErrMail()
    {
        if ($this->configRepository->get()->isErrorMail()) {
            $this->sendMail('バッチタイプをコマンドに設定してください',
                'バッチタイプをコマンドに設定してください');
        }
    }

    public function sendConnErrMail()
    {
        if ($this->configRepository->get()->isErrorMail()) {
            $this->sendMail('アトディーネ通信エラー',
                'アトディーネにて通信エラーが発生しました。詳しい情報はログをご確認ください。');
        }
    }

    /**
     * @param $orderOn
     */
    public function sendReEditMail($orderOn)
    {
        if ($this->configRepository->get()->isConditionMail()) {
            $this->sendMail('アトディーネ文字長・文字コードエラー。',
                'アトディーネでの決済に失敗しました。管理画面にて、受注ON:'.$orderOn.'の受注情報のご確認をお願いいたします。\n詳しくはログファイルをご確認ください。');
        }
    }

    /**
     * @param $orderOn
     */
    public function sendReEditConnMail($orderOn)
    {
        if ($this->configRepository->get()->isConditionMail()) {
            $this->sendMail('【要受注情報修正】アトディーネでの決済に失敗しました。',
                'JACSS後払いで入力された決済情報の項目にてエラーが発生しました。管理画面にて、受注ON:'.$orderOn."の受注のエラー情報をご確認後、受注情報の変更をお願いいたします。\n詳細はログファイルをご確認ください。");
        }
    }

    /**
     * @param $orderIds
     * @param $mOrderIds
     */
    public function sendOrderErrorMail($orderIds, $mOrderIds)
    {
        if ($this->configRepository->get()->isConditionMail()) {
            $text = '';
            if (count($orderIds)) {
                $text = 'アトディーネで入力された決済情報の項目にてエラーが発生しました。\n管理画面にて、受注ID:'.join(',', $orderIds)."の受注のエラー情報をご確認後、受注情報の変更をお願いいたします。\n詳細はログファイルをご確認ください。";
            }

            if (count($mOrderIds)) {
                $text = 'アトディーネ審査保留、受注ID:'.join(',', $mOrderIds);
            }

            $this->sendMail('アトディーネ-受注情報修正/審査保留', $text);
        }
    }
}
