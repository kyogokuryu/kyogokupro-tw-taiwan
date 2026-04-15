<?php
namespace Customize\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * @ShoppingFlow()
 *
 * 台湾版 送料無料判定プロセッサー
 * 
 * 送料無料条件:
 * - 商品小計（クーポン割引前）が delivery_free_amount (3500TWD) 以上
 * - 商品個数が delivery_free_quantity 以上
 * - 全商品が delivery_fee_free フラグ付き
 * - クーポンに delivery_free_flag が設定されている
 * - プライム会員
 *
 * 注意: 台湾版では沖縄除外ロジックは不要
 * 
 * 2026-04-15 修正: クーポン割引前の小計で送料無料判定するように変更
 * 旧ロジックではクーポン割引後の金額で判定していたため、
 * 小計3500以上でもクーポン使用時に送料が加算されるバグがあった
 */
class CustomDeliveryFeeByShippingProcessor implements ItemHolderPreprocessor
{
 
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var entityManager
     */
    protected $entityManager;

    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->entityManager = $entityManager;
    }
 
    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!($this->BaseInfo->getDeliveryFreeAmount() || $this->BaseInfo->getDeliveryFreeQuantity())) {
            return;
        }
 

        // Orderの場合はお届け先ごとに判定する.
        if ($itemHolder instanceof Order) {
            /** @var Order $Order */
            $Order = $itemHolder;
            /** @var Id $OrderId */
            $OrderId = $itemHolder->getId();

			// カスタマー取得
	        $Customer = $Order->getCustomer();

			// CouponOrderから対象クーポン取得
			$CouponOrders = $this->entityManager->getRepository('Plugin\Coupon4\Entity\CouponOrder')->findBy(['order_id' => $OrderId]);

            foreach ($Order->getShippings() as $Shipping) {

				$isFree = false;
				$itemTotalBeforeCoupon = 0;  // クーポン割引前の商品小計
				$itemQuantity = 0;
				
				$tempIsDeliveryFee = true;
                foreach ($Shipping->getOrderItems() as $Item) {

					if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
						continue;
					}

					//タイムセール割引用のダミー商品を除外
					if(strpos($Item['product_name'], 'タイムセール値引') !== false){
						continue;
					}

					// 商品個数
                    $itemQuantity += $Item->getQuantity();

					// クーポンの送料無料フラグチェック
					foreach ($CouponOrders as $couponorder) {
						$Coupons = $this->entityManager->getRepository('Plugin\Coupon4\Entity\Coupon')->findBy(['id' => $couponorder->getCouponId()]);
						foreach ($Coupons as $Coupon) {
							foreach ($Coupon->getCouponDetails() as $CouponDetail) {
								if ($CouponDetail->getDeliveryFreeFlag() == 1) {
									// 商品一致チェック
									if ($CouponDetail->getProduct() && $Item->getProduct()->getId() == $CouponDetail->getProduct()->getId()) {
										$isFree = true;
									}
									// カテゴリ一致チェック
									if ($CouponDetail->getCategory()) {
										$Categories = $Item->getProduct()->getProductCategories();
										foreach ($Categories as $Category) {
											if ($Category->getCategoryId() == $CouponDetail->getCategory()->getId()) {
												$isFree = true;
												break;
											}
										}
									}
								}
							}
						}
					}

					//--------------------
					// 商品小計（クーポン割引前）を計算
					// 送料無料判定はクーポン割引前の金額で行う
					//--------------------
					$itemTotalBeforeCoupon += $Item->getPriceIncTax() * $Item->getQuantity();

					if($Item->getProduct()->getId() == \Eccube\Entity\Product::get_prime_product_id()){
						$isFree = true;
					}

					//一つでも送料無料対象商品でなければtempIsDeliveryFeeをfalseにする
					if (!$Item->getProductClass()->delivery_fee_free) {
						$tempIsDeliveryFee = false;
					}
                }
				
				//もしカートのもの全て送料無料対象商品であれば、送料無料にする
				if ($tempIsDeliveryFee) {
					$isFree = true;
				}

				//--------------------
				// 送料無料（金額）プライム会員
				//--------------------
				if($Customer && $Customer->getPrimeMember() > 0){
					$isFree = true;
				}

				//--------------------
				// 送料無料（金額）を超えている
				// ★ クーポン割引前の商品小計で判定する
				//--------------------
				if ($this->BaseInfo->getDeliveryFreeAmount()) {
					if ($itemTotalBeforeCoupon >= $this->BaseInfo->getDeliveryFreeAmount()) {
						$isFree = true;
					}
				}
				//--------------------
				// 送料無料（個数）を超えている
				//--------------------
				if ($this->BaseInfo->getDeliveryFreeQuantity()) {
					if ($itemQuantity >= $this->BaseInfo->getDeliveryFreeQuantity()) {
						$isFree = true;
					}
				}

				//--------------------
				// 送料無料適用
				// 台湾版: 沖縄除外ロジックは不要
				//--------------------
                if ($isFree) {
                    foreach ($Shipping->getOrderItems() as $Item) {
                        if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                            $Item->setQuantity(0);
                        }
                    }
                }

            }
        }
    }
}
