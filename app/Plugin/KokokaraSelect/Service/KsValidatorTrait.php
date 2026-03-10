<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/13
 */

namespace Plugin\KokokaraSelect\Service;


use Eccube\Entity\ProductClass;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Trait KsValidatorTrait
 * @package Plugin\KokokaraSelect\Service
 *
 * @property Session $session;
 */
trait KsValidatorTrait
{
    /**
     * @param $errorCode
     *
     * @param array $params
     * @param ProductClass $ProductClass
     * @param bool $warning
     * @throws InvalidItemException
     */
    protected function throwKsInvalidItemException($errorCode, $params = [], ProductClass $ProductClass = null, $warning = false)
    {

        if ($ProductClass) {
            $productName = $ProductClass->getProduct()->getName();
            if ($ProductClass->hasClassCategory1()) {
                $productName .= ' - ' . $ProductClass->getClassCategory1()->getName();
            }
            if ($ProductClass->hasClassCategory2()) {
                $productName .= ' - ' . $ProductClass->getClassCategory2()->getName();
            }

            $params = array_merge($params, ['%product%' => $productName]);

            $message = trans($errorCode, $params);
            if (!$this->hasMessage($message)) {
                throw new InvalidItemException($message, null, $warning);
            }
        }
        $message = trans($errorCode, $params);
        if (!$this->hasMessage($message)) {
            throw new InvalidItemException($message, null, $warning);
        }
    }

    /**
     * メッセージの重複チェック
     *
     * @param $targetMessage
     * @return bool
     */
    protected function hasMessage($targetMessage)
    {
        if (empty($this->session)) {
            return false;
        }

        foreach ($this->session->getFlashBag()->peekAll() as $message) {
            if (is_array($message)) {
                foreach ($message as $item) {
                    if ($targetMessage == $item) {
                        return true;
                    }
                }
            } else {
                if ($targetMessage == $message) {
                    return true;
                }
            }
        }
    }
}
