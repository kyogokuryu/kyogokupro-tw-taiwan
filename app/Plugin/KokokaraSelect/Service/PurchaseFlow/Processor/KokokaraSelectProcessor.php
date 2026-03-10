<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/30
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow\Processor;


use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\Processor\AbstractPurchaseProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Service\KsOrderMailService;
use Plugin\KokokaraSelect\Service\KsService;

/**
 * @ShoppingFlow
 *
 * Class KokokaraSelectProcessor
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow\Processor
 */
class KokokaraSelectProcessor extends AbstractPurchaseProcessor
{

    /** @var KsService */
    protected $ksService;

    public function __construct(
        KsService $ksService
    )
    {
        $this->ksService = $ksService;
    }

    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {

        if ($target instanceof Order) {
            if ($this->ksService->isKsOrder($target)) {
                // キーワードの挿入を実施する
                $target->appendCompleteMailMessage(KsOrderMailService::KOKOKARA_SELECT_ADD_MAIL_MSG);
            }
        }
    }
}
