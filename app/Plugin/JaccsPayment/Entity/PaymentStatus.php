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

namespace Plugin\JaccsPayment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * PaymentStatus
 *
 * @ORM\Table(name="plg_jaccs_payment_payment_status")
 * @ORM\Entity(repositoryClass="Plugin\JaccsPayment\Repository\PaymentStatusRepository")
 */
class PaymentStatus extends AbstractMasterEntity
{
    //決済ステータス
    /** JACCS決済成功 */
    const JACCS_ORDER_PRE_END = '20001';
    /** JACCS審査中 */
    const JACCS_ORDER_PENDING = '20002';
    /** JACCS決済NG */
    const JACCS_ORDER_NG = '20003';
    /** ECから決済送信条件未満 */
    const JACCS_ORDER_EDIT = '20004';
    /** ECから決済エラー*/
    const JACCS_ORDER_ERROR = '20005';
    /** 審査保留 */
    const JACCS_ORDER_PENDING_MANUAL = '20006';
    /** JACCS即時審査NG */
    const JACCS_ORDER_NOW_ORDER_NG = '20007';
    /** JACCS取引キャンセル */
    const JACCS_ORDER_CANCEL = '20008';
}
