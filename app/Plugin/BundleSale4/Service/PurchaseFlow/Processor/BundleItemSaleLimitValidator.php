<?php
/**
 * This file is part of BundleSale4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\BundleSale4\Service\PurchaseFlow\Processor;

use Eccube\Entity\ItemInterface;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Annotation\CartFlow;
use Eccube\Service\PurchaseFlow\Processor\SaleLimitValidator as BaseSaleLimitValidator;

/**
 * @CartFlow
 */
class BundleItemSaleLimitValidator extends BaseSaleLimitValidator
{
    /**
     * @param ItemInterface $item
     * @param PurchaseContext $context
     *
     * @throws InvalidItemException
     */
    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isProduct()) {
            return;
        }

        foreach ($item->getProductClass()->getProduct()->getBundleItems() as $BundleItem) {
            $limit = $BundleItem->getProductClass()->getSaleLimit();
            if (is_null($limit)) {
                continue;
            }

            $quantity = $item->getQuantity() * $BundleItem->getQuantity();
            if ($limit < $quantity) {
                $this->throwInvalidItemException('front.shopping.over_sale_limit', $item->getProductClass());
            }
        }
    }
}
