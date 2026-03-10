<?php

/*
 * Project Name: ダウンロードコンテンツ販売 プラグイン for 4.0
 * Copyright(c) 2019 Kenji Nakanishi. All Rights Reserved.
 *
 * https://www.facebook.com/web.kenji.nakanishi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\SheebDlc\Entity;

use Eccube\Annotation\EntityExtension;
use Eccube\Annotation as Eccube;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Product;
use Eccube\Entity\Order;
use Eccube\Entity\ProductClass;
use Plugin\SheebDlc\PluginManager;

/**
 * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{
    /**
     * @ORM\Column(name="sheeb_dlc_first_download_datetime", type="datetime", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\DateTimeType",
     *  options={
     *    "required": false,
     *    "label": "sheeb.dlc.admin.order.sheeb_dlc_first_download_datetime",
     *  }
     * )
     */
    private $sheeb_dlc_first_download_datetime;

    /**
     * @ORM\Column(name="sheeb_dlc_download_count", type="integer", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\IntegerType",
     *  options={
     *    "required": false,
     *    "label": "sheeb.dlc.admin.order.sheeb_dlc_download_count",
     *  }
     * )
     */
    private $sheeb_dlc_download_count;
    
    /* *******************************
     *        DBには保存しない変数
     * *******************************/
    /**
     * Downloadできない理由
     * @var string
     */
    private $sheeb_dlc_error;

    /**
     * ダウンロード期日
     * @var \DateTime
     */
    private $sheeb_dlc_download_due_datetime;

    /**
     * 再ダウンロード可能期日
     * @var \DateTime
     */
    private $sheeb_dlc_viewing_datetime;

    /**
     * ダウンロード可能回数はあと何回か
     * @var int
     */
    private $sheeb_dlc_downloadable_count;

    /**
     * @return mixed
     */
    public function getSheebDlcDownloadCount()
    {
        return $this->sheeb_dlc_download_count;
    }

    /**
     * @param $sheeb_dlc_download_count
     * @return $this
     */
    public function setSheebDlcDownloadCount($sheeb_dlc_download_count)
    {
        $this->sheeb_dlc_download_count = $sheeb_dlc_download_count;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSheebDlcFirstDownloadDatetime()
    {
        return $this->sheeb_dlc_first_download_datetime;
    }

    /**
     * @param $sheeb_dlc_first_download_datetime
     * @return $this
     */
    public function setSheebDlcFirstDownloadDatetime($sheeb_dlc_first_download_datetime)
    {
        $this->sheeb_dlc_first_download_datetime = $sheeb_dlc_first_download_datetime;
        return $this;
    }

    /* *******************************
     *       DBに保存しないデータ
     * *******************************/
    
    /**
     * @return mixed
     */
    public function getSheebDlcError()
    {
        return $this->sheeb_dlc_error;
    }

    /**
     * @param $sheeb_dlc_error
     * @return $this
     */
    public function setSheebDlcError($sheeb_dlc_error)
    {
        $this->sheeb_dlc_error = $sheeb_dlc_error;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSheebDlcViewingDatetime()
    {
        return $this->sheeb_dlc_viewing_datetime;
    }

    /**
     * @param \DateTime $sheeb_dlc_viewing_datetime
     * @return $this
     */
    public function setSheebDlcViewingDatetime($sheeb_dlc_viewing_datetime)
    {
        if ($sheeb_dlc_viewing_datetime instanceof \DateTime) {
            $sheeb_dlc_viewing_datetime = clone $sheeb_dlc_viewing_datetime;
        }
        $this->sheeb_dlc_viewing_datetime = $sheeb_dlc_viewing_datetime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSheebDlcDownloadDueDatetime()
    {
        return $this->sheeb_dlc_download_due_datetime;
    }

    /**
     * @param \DateTime $sheeb_dlc_download_due_datetime
     * @return $this
     */
    public function setSheebDlcDownloadDueDatetime($sheeb_dlc_download_due_datetime)
    {
        if ($sheeb_dlc_download_due_datetime instanceof \DateTime) {
            $sheeb_dlc_download_due_datetime = clone $sheeb_dlc_download_due_datetime;
        }
        $this->sheeb_dlc_download_due_datetime = $sheeb_dlc_download_due_datetime;
        return $this;
    }

    /**
     * @return int
     */
    public function getSheebDlcDownloadableCount()
    {
        return $this->sheeb_dlc_downloadable_count;
    }

    /**
     * @param int $sheeb_dlc_downloadable_count
     * @return $this
     */
    public function setSheebDlcDownloadableCount($sheeb_dlc_downloadable_count)
    {
        $this->sheeb_dlc_downloadable_count = $sheeb_dlc_downloadable_count;
        return $this;
    }

    /* *******************************
     *             ロジック
     * *******************************/
    
    public function isDownloadable(SaleType $dlcSaleType, Config $Config, $no_check_order_status = false)
    {
        $this->reset();
        
        if (!$this->isProduct()) {
            $this->setSheebDlcError('sheeb.dlc.downloadable.error.not_product');
            return false;
        }
        /**
         * @var $Product Product
         * @var $Order Order
         */
        $Product = $this->getProduct();

        // --- Check: 販売種別が違うならダウンロードできない ---
        $judge = (function (ProductClass $ProductClass, SaleType $dlcSaleType) {
            if ($ProductClass->getSaleType()->getId() !== $dlcSaleType->getId()) {
                $this->setSheebDlcError('sheeb.dlc.downloadable.error.saletype_is_not_dlc');
                return false;
            }
            
            return true;
        })($this->getProductClass(), $dlcSaleType);
        if (!$judge) { return false; }
        

        // 商品の制約
        $now = new \DateTime();
       
        // 現在のダウンロード状況
        $Order = $this->getOrder();
        $order_datetime = $Order->getOrderDate();

        // --- Check: 注文が完了していないならダウンロードできない ---
        if (!($order_datetime instanceof \DateTime)) {
            // 注文が完了していない
            $this->setSheebDlcError('sheeb.dlc.downloadable.error.yet_order_complete');
            return false;
        }

        // --- Check: 設定で指定した受注ステータスではないとダウンロードできない ---
        $judge = (function (Order $Order, Config $Config, $no_check_order_status) {
            $OrderStatus = $Order->getOrderStatus();

            // 注文取り消し・返品の場合は常にダウンロードさせない
            $ban_status = [OrderStatus::CANCEL, OrderStatus::RETURNED];
            if (in_array($OrderStatus->getId(), $ban_status)) {
                // 設定の受注ステータスではない
                $this->setSheebDlcError('sheeb.dlc.downloadable.error.no_support_order_status');
                return false;
            }

            // 販売者側の問題なので, 場合によってはチェックしないで通す
            // (注文された後に、OrderStatusを変更していない状態など)
            if ($no_check_order_status) {
                return true;
            }

            // 管理画面で指定したステータスかどうか確認
            $allow_status_ids = explode(PluginManager::SEPARATOR, $Config->getAvailableOrderStatus());
            if (!in_array($OrderStatus->getId(), $allow_status_ids)) {
                // 設定の受注ステータスではない
                $this->setSheebDlcError('sheeb.dlc.downloadable.error.no_support_order_status');
                return false;
            }
            return true;
        })($Order, $Config, $no_check_order_status);
        if (!$judge) { return false; }

        // --- Check: ダウンロード可能日を過ぎていたらダウンロードできない ---
        $judge = (function (\DateTime $now, \DateTime $order_datetime, int $download_due_days) {
            // 無期限設定
            if ($download_due_days <= 0) {
                return true;
            }
            
            // ダウンロード可能日数分進める
            $wk_datetime = clone $order_datetime;
            $wk_datetime->modify("+{$download_due_days} days");
            
            if ($now > $wk_datetime) {
                $this->setSheebDlcError('sheeb.dlc.downloadable.error.expired_first_download');
                return false;
            }
            
            // 期日セット
            $this->setSheebDlcDownloadDueDatetime($wk_datetime);
            return true;
        })($now, $order_datetime, $Product->getSheebDlcDownloadDueDays() ?? PluginManager::DEFAULT_DOWNLOAD_DUE_DAYS);
        if (!$judge) { return false; }

        // --- Check: 再ダウンロード可能日を過ぎていたらダウンロードできない ---
        $judge = (function (\DateTime $now, $first_download_datetime, int $download_viewing_days) {
            // 無期限設定
            if ($download_viewing_days <= 0) {
                return true;
            }

            // ダウンロードしたことなければこのチェックは通過
            if (!($first_download_datetime instanceof \DateTime)) {
                return true;
            }
            
            // ダウンロード可能日数分進める
            $wk_datetime = clone $first_download_datetime;
            $wk_datetime->modify("+{$download_viewing_days} days");

            if ($now > $wk_datetime) {
                $this->setSheebDlcError('sheeb.dlc.downloadable.error.expired_download_again');
                return false;
            }

            // 期日セット
            $this->setSheebDlcViewingDatetime($wk_datetime);
            return true;
        })($now, $this->getSheebDlcFirstDownloadDatetime(), $Product->getSheebDlcViewingDays() ?? PluginManager::DEFAULT_VIEWING_DAYS);
        if (!$judge) { return false; }

        // --- Check: ダウンロード回数を超えていたらダウンロードできない ---
        $judge = (function ($downloaded_count, int $downloadable_count) {
            // 無期限設定
            if ($downloadable_count <= 0) {
                return true;
            }

            // ダウンロードしたことなければこのチェックは通過
            if (!is_int($downloaded_count)) {
                // 残数セット
                $this->setSheebDlcDownloadableCount($downloadable_count);
                return true;
            }

            if ($downloaded_count >= $downloadable_count) {
                $this->setSheebDlcError('sheeb.dlc.downloadable.error.over_count');
                return false;
            }

            // 残数セット
            $this->setSheebDlcDownloadableCount($downloadable_count - $downloaded_count);
            return true;
        })($this->getSheebDlcDownloadCount(), $Product->getSheebDlcDownloadableCount() ?? PluginManager::DEFAULT_DOWNLOADABLE_COUNT);
        if (!$judge) { return false; }

        return true;
    }

    private function reset()
    {
        $this->setSheebDlcError(null);
        $this->setSheebDlcDownloadDueDatetime(null);
        $this->setSheebDlcViewingDatetime(null);
        $this->setSheebDlcDownloadableCount(null);
    }

    public function getState()
    {
        return [
            'records' => [
                'sheeb_dlc_first_download_datetime' => $this->getSheebDlcFirstDownloadDatetime(),
                'sheeb_dlc_download_count' => $this->getSheebDlcDownloadCount(),
            ],
            'flush' => [
                'sheeb_dlc_error' => $this->getSheebDlcError(),
                'sheeb_dlc_viewing_datetime' => $this->getSheebDlcViewingDatetime(),
                'sheeb_dlc_download_due_datetime' => $this->getSheebDlcDownloadDueDatetime(),
                'sheeb_dlc_downloadable_count' => $this->getSheebDlcDownloadableCount(),
            ]
        ];
    }
}
