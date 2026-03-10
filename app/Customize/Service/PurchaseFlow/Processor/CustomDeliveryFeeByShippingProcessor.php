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
 * Class CustomDeliveryFeeByShippingProcessor
 * @package Customize\Service\PurchaseFlow\Processor
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

			// FOrce Free
			$force_free = false;

            foreach ($Order->getShippings() as $Shipping) {

				$isFree = false;
				$itemTotal = 0;
				$itemQuantity = 0;
				
				$tempIsDeliveryFee = true;
                foreach ($Shipping->getOrderItems() as $Item) {

					if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
// log_info('********** スキップ');
						continue;
					}

					//タイムセール割引用のダミー商品をを除外 20210628 kikuzawa
					if(strpos($Item['product_name'], 'タイムセール値引') !== false){
						continue;
					}

// log_info('********** item->'.$Item->getProduct()->getId().' : '.$Item->getPriceIncTax().' : '.$Item->getQuantity());

					// クーポン割引
					$couponType = 0;	// 0：商品・カテゴリ　1：全体
					$couponDiscountRate = 0;
					$couponDiscountPrice = 0;
					// 商品個数
                    $itemQuantity += $Item->getQuantity();

					// CouponOrderから対象クーポン取得
					foreach ($CouponOrders as $couponorder) {
						// Couponから対象クーポン取得
						$Coupons = $this->entityManager->getRepository('Plugin\Coupon4\Entity\Coupon')->findBy(['id' => $couponorder->getCouponId()]);
						foreach ($Coupons as $Coupon) {
// log_info('********** クーポンタイプ->'.$Coupon->getCouponType());
							if( $Coupon->getCouponType() == 3 ){
// log_info('********** 全商品->'.$Coupon->getDiscountRate().' : '.$Coupon->getDiscountPrice());
								//--------------------
								// クーポンタイプ：全商品
								//
								// 「全商品」を外しているので、ここが処理されることはないが一応残す
								//--------------------
								$couponType = 1;	// 0：商品・カテゴリ　1：全体
								$couponDiscountRate = $Coupon->getDiscountRate();
								$couponDiscountPrice = $Coupon->getDiscountPrice();
							} else {
								//--------------------
								// クーポンタイプ：商品・カテゴリ
								//--------------------
								// CouponDetailから対象商品の割引情報取得
								foreach ($Coupon->getCouponDetails() as $CouponDetail) {
									if ($CouponDetail->getProduct()) {
// log_info('********** 商品->'.$Item->getProduct()->getId().' == '.$CouponDetail->getProduct()->getId().' '.$CouponDetail->getProductName());
										//--------------------
										// 商品
										//--------------------
										if ($Item->getProduct()->getId() == $CouponDetail->getProduct()->getId()) {
// log_info('********** 商品詳細->'.$CouponDetail->getDetailDiscountRate().'%引き　送料判定->'.$CouponDetail->getDeliveryFreeFlag());
											// 割引取得
											if ($CouponDetail->getDetailDiscountRate() != '' && $CouponDetail->getDetailDiscountRate() != 0) {
												$couponDiscountRate = $CouponDetail->getDetailDiscountRate();
											}
											// プロモーションコード「送料なし」
											if ($CouponDetail->getDeliveryFreeFlag() == 1) {
// log_info('********** 送料なし');
												$isFree = true;
											}
										}
									} else if($CouponDetail->getCategory()) {
// log_info('********** カテゴリ');
										//--------------------
										// カテゴリ
										//--------------------
										$Categories = $Item->getProduct()->getProductCategories();
										foreach ($Categories as $Category) {
// log_info('********** '.$Category->getCategoryId().' == '.$CouponDetail->getCategory()->getId().' '.$CouponDetail->getCategoryFullName());
											if ($Category->getCategoryId() == $CouponDetail->getCategory()->getId()) {
// log_info('********** カテゴリ詳細->'.$CouponDetail->getDetailDiscountRate().'%引き　送料判定->'.$CouponDetail->getDeliveryFreeFlag());
												// 割引取得
												if ($CouponDetail->getDetailDiscountRate() != '' && $CouponDetail->getDetailDiscountRate() != 0) {
													$couponDiscountRate = $CouponDetail->getDetailDiscountRate();
												}
												// プロモーションコード「送料なし」
												if ($CouponDetail->getDeliveryFreeFlag() == 1) {
// log_info('********** 送料なし');
													$isFree = true;
												}
												// ループ終了
												break;
											}
										}
									}
								}
								//--------------------
								// 個別商品・カテゴリの割引率が設定されていない場合は、全商品の割引率・割引額を適用
								//--------------------
								if( $couponDiscountRate == 0 && $couponDiscountPrice == 0 ){
// log_info('********** 個別設定がないので、全設定を適用->'.$Coupon->getDiscountRate().' : '.$Coupon->getDiscountPrice());
									$couponDiscountRate = $Coupon->getDiscountRate();
									$couponDiscountPrice = $Coupon->getDiscountPrice();
								}
							}
						}
					}

// log_info('********** couponDiscountRate->'.$couponDiscountRate);
// log_info('********** couponDiscountPrice->'.$couponDiscountPrice);
					//--------------------
					// プロモーションコード適用割引後の金額で小計
					//--------------------
					if( $couponType == 0 ){
						// 適用は「商品・カテゴリ」のみ
						if( $couponDiscountRate != 0 ){
// log_info('********** 割引適用率あり');
							// 割引適用
							$coupontax = ($Item->getPriceIncTax() * $Item->getQuantity()) * $couponDiscountRate / 100;
							$itemTotal += ($Item->getPriceIncTax() * $Item->getQuantity()) - $coupontax;
						} else if( $couponDiscountPrice != 0 ){
// log_info('********** 割引適用額あり');
							// 割引適用　まとめて全体から割り引く
							$itemTotal += ($Item->getPriceIncTax() * $Item->getQuantity());
						} else {
// log_info('********** 割引適用なし');
							// 通常小計
							$itemTotal += $Item->getPriceIncTax() * $Item->getQuantity();
						}
					} else {
						// 通常小計（全体）
						$itemTotal += $Item->getPriceIncTax() * $Item->getQuantity();
					}
// log_info('********** 小計->'.$itemTotal);


					if($Item->getProduct()->getId() == \Eccube\Entity\Product::get_prime_product_id()){
						$isFree = true;
						$force_free = true;
					}

					//一つでも送料無料対象商品でなければtempIsDeliveryFeeをfalseにする
					if (!$Item->getProductClass()->delivery_fee_free) {
						// $isFree = true;
						$tempIsDeliveryFee = false;
					}
                }
				
				//もしカートのもの全て送料無料対象商品であれば、送料無料にする
				if ($tempIsDeliveryFee) {
					$isFree = true;
				}
				
				//--------------------
				// 全商品の場合は、トータル額から割引適用
				//
				// 「全商品」を外しているので、ここが処理されることはないが一応残す
				//--------------------
				if( $couponType == 1 ){
					if( $couponDiscountRate != 0 ){
// log_info('********** （全商品）割引適用率あり');
						// 割引適用
						$coupontax = $itemTotal * $couponDiscountRate / 100;
						$itemTotal = $itemTotal - $coupontax;

					} else if( $couponDiscountPrice != 0 ){
// log_info('********** （全商品）割引適用額あり');
						// 割引適用
						$itemTotal = $itemTotal - $couponDiscountPrice;
					}
				}else{
					if(isset($couponDiscountPrice) && $couponDiscountPrice > 0){
						$itemTotal -= $couponDiscountPrice;
					}
				}


				//--------------------
				// 送料無料（金額）プライム会員
				//--------------------
				if($Customer && $Customer->getPrimeMember() > 0){
					$isFree = true;
				}


				//--------------------
				// 送料無料（金額）を超えている
				//--------------------
				if ($this->BaseInfo->getDeliveryFreeAmount()) {
// log_info('********** itemTotal->'.$itemTotal);
					if ($itemTotal >= $this->BaseInfo->getDeliveryFreeAmount()) {
// log_info('********** 5500円以上');
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
				//--------------------
                if ($isFree) {
// log_info('********** 送料無料適用');
                    foreach ($Shipping->getOrderItems() as $Item) {
                        if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
// log_info('********** 適用');
                            $Item->setQuantity(0);
							if($Customer && $Customer->getPrimeMember() > 0){

							}elseif($force_free){
							
							}else{
								// 沖縄は送料無料条件適用を除外する 20211110 kikuzawa
								$shippingPref = $Shipping->getPref()['id'];
								if ($shippingPref == 47) {
									$Item->setQuantity(1);
								}
							}
                        }
                    }
                }

            }
        }
    }
}