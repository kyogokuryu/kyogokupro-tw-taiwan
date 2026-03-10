<?php

namespace Plugin\SortProduct\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\SortProduct\Entity\SortProduct;
use Symfony\Bridge\Doctrine\RegistryInterface;


class SortProductRepository extends AbstractRepository
{
    public function __construct( RegistryInterface $registry)
    {
        parent::__construct($registry, SortProduct::class);
    }

    /*
     * 全レコードをrankのDESC順にソートして返す
     * $result = array(
     *            0 => array( "product_id" => "146", "rank"=>"500"),
     *            1 => array( "product_id" => "287", "rank"=>"300"),
     *            2 => array( "product_id" => "133", "rank"=>"100"),
     *         )
     */
    public function getAllRecordOrderByRank()
    {
        $qb = $this->createQueryBuilder('sp')
            ->orderBy('sp.sort_no', 'DESC');

        $qb->select('sp.product_id');
        $qb->addSelect('sp.sort_no');

//        return $qb->getQuery();

        $result = $qb->getQuery()->getResult();

        return $result;
    }
    /*
     * 全レコードをrankのDESC順にソートして返す
     *
     * @Return entityの配列
     */
    public function getAllRecordOrderByRankDESC()
    {
        $qb = $this->createQueryBuilder('sp')
            ->orderBy('sp.sort_no', 'DESC');

        $entityArray = $qb->getQuery()->getResult();

        return $entityArray;
    }
    /*
     * 全レコードをrankのASC順にソートして返す
     *
     * @Return entityの配列
     */
    public function getAllRecordOrderByRankASC()
    {
        $qb = $this->createQueryBuilder('sp')
            ->orderBy('sp.sort_no', 'ASC');

        $entityArray = $qb->getQuery()->getResult();

        return $entityArray;
    }
    /*
     * 引数で渡された商品ID一覧に該当するレコードをrankのDESC順にソートして返す
     * $result = array(
     *            0 => array( "product_id" => "146", "sort_no"=>"500"),
     *            1 => array( "product_id" => "287", "sort_no"=>"300"),
     *            2 => array( "product_id" => "133", "sort_no"=>"100"),
     *         )
     */
    public function getRecordOrderByRank($productIds)
    {
        $qb = $this->createQueryBuilder('sp');

        $qb->where($qb->expr()->in('sp.product_id', $productIds));

        $qb->orderBy('sp.sort_no', 'DESC');

        $qb->select('sp.product_id');
        $qb->addSelect('sp.sort_no');

//        return $qb;

        $result = $qb->getQuery()->getResult();

        return $result;
    }
    /*
     * 引数で渡された商品ID一覧をrankのDESC順にソートして返す
     * $result = array(
     *            0 => array( "product_id" => "146"),
     *            1 => array( "product_id" => "287"),
     *            2 => array( "product_id" => "133"),
     *         )
     */
    public function getProductIdOrderByRank($productIds)
    {
        if (empty($productIds)) {
            $productIds = array(null);
        }

        $qb = $this->createQueryBuilder('sp');

        $qb->where($qb->expr()->in('sp.product_id', $productIds));

        $qb->orderBy('sp.sort_no', 'DESC');

        $qb->select('sp.product_id');

//        return $qb;

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /*
     * 引数で渡された商品ID一覧のrank一覧をDESC順で返す
     * $result = array(
     *            0 => array( "rank" => "100"),
     *            1 => array( "rank" => "50"),
     *            2 => array( "rank" => "2"),
     *         )
     */
    public function getProductRankOrderByRank($productIds)
    {
        $qb = $this->createQueryBuilder('sp');

        if (empty($productIds)) {
            $productIds = array(null);
        }

        $qb->where($qb->expr()->in('sp.product_id', $productIds));

        $qb->orderBy('sp.sort_no', 'DESC');

        $qb->select('sp.sort_no');

//        return $qb;

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    // 現在設定されている最大のrankを返す
    public function getMaxRank()
    {
        $qb = $this->createQueryBuilder('sp');
        $qb->select('MAX(sp.sort_no) as max_rank');
        $result = $qb->getQuery()->getResult();

        return $result[0]['max_rank'];  // <-結果が1個しかないので
    }

    // SortProductテーブルでrankが設定されていないレコード(rank==null)を探す
    public function getNoRankRecords()
    {
        $noRankRecords = $this->createQueryBuilder('sp')
            ->where('sp.sort_no is NULL')
            ->getQuery()->getResult();

        return $noRankRecords;
    }

}
