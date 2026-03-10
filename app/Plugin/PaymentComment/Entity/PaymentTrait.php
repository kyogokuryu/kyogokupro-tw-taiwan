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
 * @Eccube\EntityExtension("Eccube\Entity\Payment")
 */
trait PaymentTrait
{
        /**
         * @var string|null
         *
         * @ORM\Column(name="site_ex", type="text", nullable=true)
         */
        private $site_ex;

        /**
         * @var string|null
         *
         * @ORM\Column(name="mail_ex", type="text", nullable=true)
         */
        private $mail_ex;

        /**
         * Set siteEx.
         *
         * @param string|null $siteEx
         *
         * @return Payment
         */
		 
        public function setSiteEx($siteEx = null)
        {
            $this->site_ex = $siteEx;

            return $this;
        }

        /**
         * Get siteEx.
         *
         * @return string|null
         */
        public function getSiteEx()
        {
            return $this->site_ex;
        }

        /**
         *
         * @param string|null $mailEx
         *
         * @return Payment
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
