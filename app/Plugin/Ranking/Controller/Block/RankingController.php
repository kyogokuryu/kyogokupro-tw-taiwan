<?php
namespace Plugin\Ranking\Controller\Block;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Repository\OrderItemRepository;
use Eccube\Application;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\OrderItem;
use Plugin\Ranking\Repository\ConfigRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RankingController extends AbstractController
{
	/**
	 * @var RequestStack
	 */
	protected $session;

	/**
	 * @var ProductRepository
	 */
	protected $productRepository;
	
	/**
	 * @var OrderItemRepository
	 */
	protected $orderItemRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

	public function __construct(
		SessionInterface $session,
		ProductRepository $productRepository,
		OrderItemRepository $orderItemRepository,
        ConfigRepository $configRepository,
        ContainerInterface $container
	) {
		$this->session = $session;
		$this->productRepository = $productRepository;
		$this->orderItemRepository = $orderItemRepository;
        $this->configRepository = $configRepository;
        $this->containerInterface = $container;
	}

	/**
	 * @Route("/block/ranking", name="block_ranking")
	 * @Template("Block/ranking.twig")
	 *
	 * @param Request $request
	 * @return array
	 */
	public function index(Request $request, Application $app) {
		$rankingProducts = [];

		// ランキング設定情報取得
		$Config = $this->configRepository->get();

		// 注文日期間指定
		$targetPeriod = $Config->getTargetPeriod();
		// 終了日(当日0時0分0秒)
		$endDate = date("Y/m/d 0:00:00");
		$endDate = date("Y/m/d H:i:s", strtotime('-9hour'));
		// 開始日
		if		($targetPeriod == 5){$startDate = date("Y/m/d 0:00:00", strtotime('-1 month'));	// 設定値が過去1ヶ月の場合、1ヶ月前の0時0分0秒
		}else if($targetPeriod == 4){$startDate = date("Y/m/d 0:00:00", strtotime('-3 week'));	// 設定値が過去3週間の場合、3週間前の0時0分0秒
		}else if($targetPeriod == 3){$startDate = date("Y/m/d 0:00:00", strtotime('-2 week'));	// 設定値が過去2週間の場合、2週間前の0時0分0秒
		}else if($targetPeriod == 2){$startDate = date("Y/m/d 0:00:00", strtotime('-1 week'));	// 設定値が過去1週間の場合、1週間前の0時0分0秒
		}else						{$startDate = date("Y/m/d 0:00:00", strtotime('-1 day'));	// それ以外の場合は設定値が前日と見なし、前日の0時0分0秒
		}

		// 自動ランキングデータ取得
		// * 商品別注文データを注文日で範囲指定、重複する商品をグルーピング化し、注文数が多い商品順に取得
		$query = $this->orderItemRepository->createQueryBuilder('oi');
		$query = $query
			->select("COUNT(o.order_date) AS order_count, p.id AS product_id")
			->innerJoin('oi.Order', 'o')
			->innerJoin('oi.Product', 'p')
			->andWhere('oi.Product IS NOT NULL')					// 商品ID必須(手数料等を除外)
			->andWhere('o.order_date IS NOT NULL')					// 注文日必須
			->andWhere('o.OrderStatus != '.OrderStatus::RETURNED)	// 返品を除外
			->andWhere('o.OrderStatus != '.OrderStatus::CANCEL)		// 注文取消しを除外
			->andWhere("o.order_date >= '".$startDate."'")			// 注文日範囲指定 開始日
			->andWhere("o.order_date < '".$endDate."'")				// 注文日範囲指定 終了日
			->groupBy('p.id')
			// ->orderBy('order_count', 'DESC')
			->addOrderBy('order_count', 'DESC')
			->addOrderBy('product_id', 'DESC')
		;
			
		// ギフトバッグ商品を除外
		$giftbagProductIdList = explode(",", env('ECCUBE_GIFTBAG_ID_LIST', ""));
		$giftbagProductIdList = array_map('intval', $giftbagProductIdList);    
		if(count($giftbagProductIdList) > 0){
			$query
				->andWhere($query->expr()->notin('p.id', ':giftbagProductIdList'))
				->setParameter('giftbagProductIdList', $giftbagProductIdList);
		}

		// GARAGEカテゴリ商品を除外
		$garageCategoryId = (int)env('GARAGE_PRODUCT_CATEGORY_ID', "");
		if(is_numeric($garageCategoryId)){
			$query
				// ->leftJoin('p.ProductCategories', 'grPct')
				// ->leftJoin('grPct.Category', 'grC')
				// ->andWhere('(
				// 	grC.id != :garageCategoryId or
				// 	grC.id IS NULL
				// )')
				// ->setParameter('garageCategoryId', $garageCategoryId);
				->leftJoin('p.ProductCategories', 'grPct', 'WITH', 'grPct.category_id = :garageCategoryId')
				->andWhere('grPct.category_id IS NULL')
				->setParameter('garageCategoryId', $garageCategoryId);

		}

		// 「非公開」「廃止」の商品を除外
		$query
			->andWhere('p.Status = :Status')
			->setParameter('Status', ProductStatus::DISPLAY_SHOW)
		;

		$query = $query->setMaxResults(10)->getQuery();

		// echo $query->getSQL();
		$autoRankingList = $query->getResult();
		// echo var_dump($autoRankingList)."<br><br>";

		// ランキング枠設定情報をもとにランキングデータ配列生成
		// array(
		//	0 => ['product_id' => 商品ID, 'order_count' => 注文数],	# 枠1
		//	1 => ['product_id' => 商品ID, 'order_count' => 注文数]	# 枠2
		//  .
		//  .
		//  .
		// )
		$rankingList = [];
		$configArray = $this->configRepository->createQueryBuilder('cfg')->where('cfg.id = 1')->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)[0];
		for($i = 1; $i <= 10; $i++){
			if(!isset($configArray['frame'.$i.'_type']) || !isset($configArray['frame'.$i.'_value'])) continue;

			// ランキング枠タイプが自動の場合、該当順位の商品IDを設定
			// (自動ランキングデータに該当順位のデータが存在しない場合は注文0件と見なしスキップ)
			if(!$configArray['frame'.$i.'_type']){
				if(!isset($autoRankingList[$i - 1])) continue;
				$rankingList[] = [
					'product_id' => $autoRankingList[$i - 1]['product_id'],
					'order_count' => $autoRankingList[$i - 1]['order_count']
				];
			
			// それ以外の場合ランキング枠タイプが手動と見なし、指定商品IDを設定
			}else{
				$rankingList[] = [
					'product_id' => $configArray['frame'.$i.'_value'],
					'order_count' => null
				];
			}
		}
		// echo var_dump($rankingList).'<br><br>';
		
		// 該当する商品IDの一次配列を生成し、全ての商品情報取得
		$productIdList = array_column($rankingList, 'product_id');
		// echo var_dump($productIdList)."<br><br>";
		$query = $this->productRepository->createQueryBuilder('p');
		$query = $query
			->select('p')
			->andWhere($query->expr()->in('p.id', ':productIdList'))
			->setParameter('productIdList', $productIdList)
			->getQuery()
		;
		$productList = $query->getResult();

		// ランキングデータ配列に商品情報を追加
		$rankingProducts = array_map(function($item) use($productList){
			$targetProduct = array_merge(
				array_filter($productList, function($product) use($item){
					return ($product['id'] == $item['product_id']);
				})
			);
			$item['Product'] = $targetProduct[0] ?? [];

			$product = $item['Product'];
            $rateObj = $this->containerInterface->get(\Plugin\ProductReview4\Repository\ProductReviewRepository::class);
			if($rateObj && is_object($product)){
				$rate = $rateObj->getAvgAll($product);
				$product->review_ave = round($rate['recommend_avg']);
				$product->review_cnt = intval($rate['review_count']);
			}
			return $item;
		}, $rankingList);
		
		return [
			'AutoPlay' => $Config->getSliderAutoPlay(),
			'SliderDesign' => $Config->getSliderDesign(),
			'RankingProducts' => $rankingProducts
		];
	}
}
