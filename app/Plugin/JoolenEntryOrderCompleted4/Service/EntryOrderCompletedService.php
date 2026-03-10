<?php

/*
 * Plugin Name: JoolenEntryOrderCompleted4
 *
 * Copyright(c) joolen inc. All Rights Reserved.
 *
 * https://www.joolen.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JoolenEntryOrderCompleted4\Service;

use Eccube\Entity\Customer;
use Eccube\Entity\Order;

class EntryOrderCompletedService
{
    /**
     * Set from order.
     *
     * @param \Eccube\Entity\Customer $Customer
     * @param \Eccube\Entity\Order $Order
     *
     * @return \Eccube\Entity\Customer
     */
    public function setFromOrder(Customer $Customer,Order $Order)
    {
        $Customer
            ->setName01($Order->getName01())
            ->setName02($Order->getName02())
            ->setKana01($Order->getKana01())
            ->setKana02($Order->getKana02())
            ->setCompanyName($Order->getCompanyName())
            ->setPhoneNumber($Order->getPhoneNumber())
            ->setPostalCode($Order->getPostalCode())
            ->setPref($Order->getPref())
            ->setAddr01($Order->getAddr01())
            ->setAddr02($Order->getAddr02())
            ->setEmail($Order->getEmail());
        return $Customer;
    }
}
