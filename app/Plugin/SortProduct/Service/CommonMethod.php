<?php


namespace Plugin\SortProduct\Service;

use Doctrine\ORM\EntityManager;
use Plugin\SortProduct\Entity\SortProduct;
use Plugin\SortProduct\Repository\SortProductRepository;

/**
 * 複数クラスで統一したロジックで動かないといけないメソッドをここに集約する
 */
class CommonMethod
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var SortProductRepository
     */
    private $sortProductRepository;

    /**
     * CommonMethod constructor.
     * @param EntityManager $entityManager
     * @param SortProductRepository $sortProductRepository
     */
    public function __construct(EntityManager $entityManager, SortProductRepository $sortProductRepository)
    {
        $this->entityManager = $entityManager;
        $this->sortProductRepository = $sortProductRepository;
    }


    // 並び替え番号の現在の最大値を求める
    public function getMaxRank(){
        $maxRank = $this->sortProductRepository->getMaxRank();

        return $maxRank;
    }


    // 1. Productテーブルにある商品で、SortProductテーブルに設定されていないレコードを探して、SortProductテーブルにrankを登録する
    // 2. SortProductテーブルでrankが設定されていないレコードを探して、rankを設定する
    // 並び替え番号がnullの場合は、商品番号順に最大値+1から順に番号をふる
    public function setNewRank(){

        // 1. Productテーブルにある商品で、SortProductテーブルに設定されていないレコードを探して、SortProductテーブルにrankを登録する
        //   Productの全レコードの商品IDを取得
        $productIds = $this->entityManager->createQueryBuilder()
            ->select("p.id")
            ->from('Eccube\Entity\Product', "p")
            ->getQuery()->getResult();
        //   全商品を対象に、rank値が設定されていない商品を探し、なければ設定する
        foreach($productIds as $productId){
            $this->hashProductIdToRank($productId["id"]);  // rank値が設定されていない商品を探し、なければ設定する(メソッドの戻値は、この後 使用しないので保管しない)
        }

        // 2. SortProductテーブルでrankが設定されていないレコードを探して、rankを設定する
        //   SortProductテーブルでrankが設定されていないレコード(rank==null)を探す
        $noRankRecords = $this->sortProductRepository->getNoRankRecords();
        $newRank = self::getMaxRank() + 1;  // ランクがない物は、最大値+1から振る
        //   rankがnullのレコードへ、新規rank値を登録
//        foreach($noRankRecords as $noRankRecord){
//            if($noRankRecord->getRank()==null){  // ねんのため再確認
//                //並び替え番号がnullの場合は、商品番号順に最大値+1から順に番号をふる
//                $noRankRecord->setRank($newRank++);
//                $app['orm.em']->persist($noRankRecord);
//            }
//        }
        //   新規rankは商品コードを割り当てる
        /** @var SortProduct $noRankRecord */
        foreach($noRankRecords as $noRankRecord){
            if($noRankRecord->getSortNo() == null){  // ねんのため再確認
                //並び替え番号がnullの場合は、商品番号順に最大値+1から順に番号をふる
                $noRankRecord->setSortNo($noRankRecord->getProductId());
                $this->entityManager->persist($noRankRecord);
            }
        }
        $this->entityManager->flush();
    }

    // [product_id]を入力し、rankを返すハッシュ
    // もし、指定した商品($productId)のRANK情報がなければ、新規登録する
    public function hashProductIdToRank($productId){

        $sortProductRecord = $this->sortProductRepository
            ->findOneBy(array('product_id'=>$productId));

        // もし、RANKデータがなければ、新規登録する
//        if($sortProductRecord===null){
//            $new_entity_SortProduct = new SortProduct();
//            $new_entity_SortProduct->setProduct_id($productId);
//            $newRank = self::getMaxRank() + 1;  // 新しいrankは、現在のrankの最大値+1を割り当てる
//            $new_entity_SortProduct->setRank($newRank);
//            $app['orm.em']->persist($new_entity_SortProduct);
//            $app['orm.em']->flush();
//            $sortProductRecord = $new_entity_SortProduct;
//        }
        // 新rankにはproductIdを割り当てる
        if($sortProductRecord===null){
            $new_entity_SortProduct = new SortProduct();
            $new_entity_SortProduct->setProductId($productId);
            $new_entity_SortProduct->setSortNo($productId);
            $this->entityManager->persist($new_entity_SortProduct);
            $this->entityManager->flush();
            $sortProductRecord = $new_entity_SortProduct;
        }

        return $sortProductRecord->getSortNo();

    }

    // 商品IDの重複を排除する
    public function renewProductId(){

        $repoSortProduct = $this->sortProductRepository;

        // 1. 現在のrank順(DESC)で全レコードを取得
        $SortProductArray = $repoSortProduct->getAllRecordOrderByRankDESC();

        // 2. 重複したレコードはremoveする
        $isRedundancy = array();  // 商品コードの重複排除チェック $productsへ格納済みの商品はtrueで記録する
        /** @var SortProduct $SortProduct */
        foreach($SortProductArray as $SortProduct){
            if(isset($isRedundancy[$SortProduct->getProductId()]) && $isRedundancy[$SortProduct->getProductId()]==true){
                $this->entityManager->remove($SortProduct);
            }else{
                $isRedundancy[$SortProduct->getProductId()]=true;
            }
        }
        $this->entityManager->flush();
    }

    // rankが重複しているものをなくすため、rankを振り直す
    public function renewRank(){

        $repoSortProduct = $this->sortProductRepository;

        // 1. 現在のrank順(ASC)で全レコードを取得
        $SortProductArray = $repoSortProduct->getAllRecordOrderByRankASC();

        // 2. 1から順に振り直す
        $rank=1;
        /** @var SortProduct $SortProduct */
        foreach($SortProductArray as $SortProduct){
//            $id=$SortProduct->getId();
//            $targetSortProduct = $repoSortProduct->findOneBy(array('id'=>$id));
//            $targetSortProduct->setRank($rank++);
//            $app['orm.em']->persist(targetSortProduct);
            $SortProduct->setSortNo($rank++);
            $this->entityManager->persist($SortProduct);
        }
        $this->entityManager->flush();

    }
}
