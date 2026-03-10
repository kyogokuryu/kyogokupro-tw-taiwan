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

namespace Plugin\PaymentComment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
        /**
         * @var string|null
         *
         * @ORM\Column(name="mail_ex", type="text", nullable=true)
         */
        private $mail_ex;

        /**
         *
         * @param string|null $mailEx
         *
         * @return Order
         */
        public function setMailEx($mailEx = null)
        {
            $this->mail_ex = $mailEx;

            return $this;
        }
        /**
         *
         * @return string|null
         */
        public function getMailEx()
        {
            return $this->mail_ex;
        }
}
