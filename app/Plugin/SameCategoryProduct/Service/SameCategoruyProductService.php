<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SameCategoryProduct\Service;

use Eccube\Entity\Category;
use Eccube\Entity\Product;
use Plugin\SameCategoryProduct\Repository\SameCategoryProductRepository;

/**
 * 同カテゴリ商品サービス、
 *
 * @author Masaki Okada
 */
class SameCategoruyProductService
{

    /** @var SameCategoryProductRepository */
    protected $productRepository;

    /**
     * コンストラクタ
     *
     * @param SameCategoryProductRepository $productRepository
     */
    public function __construct(SameCategoryProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * 同じカテゴリの商品を返却する。<br/>
     * 複数のカテゴリに所属する場合、一番若いIDのカテゴリの商品を順に表示する
     *
     * @param int $product_id
     * @return \Eccube\Entity\Product
     */
    public function getSameCategoryProducts($product_id)
    {
        return $this->productRepository->getSameCategoryProducts($product_id);
    }
}
