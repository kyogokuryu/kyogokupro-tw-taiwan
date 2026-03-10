<?php

namespace Plugin\Collection\Service;

use Doctrine\Common\Collections\Criteria;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\ProductRepository;

class CalculateService
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;


    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * calculateService constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductClassRepository $productClassRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductClassRepository $productClassRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productClassRepository = $productClassRepository;
    }

    public function calculate($productId)
    {
        $Product = $this->productRepository->find($productId);
        $ProductClasses = $Product->getProductClasses();

        $unlimitedCriteria = Criteria::create()
            ->where(Criteria::expr()->eq('stock_unlimited', 1));
        $unlimited = $ProductClasses->matching($unlimitedCriteria);

        if ($unlimited->count() > 0) {
            // ProductClasses having stock_unlimited = 1 exist at least one
            return trans('collection.admin.collection.product_class.unlimited');

        } else {
            // get ProductClass's stock column value
            $limitedCriteria = Criteria::create()
            ->where(Criteria::expr()->eq('stock_unlimited', 0));
            $limited = $ProductClasses->matching($limitedCriteria);
            $stocks = $limited->map(function ($ProductClass) {
                return intval($ProductClass->getStock());
            })->toArray();

            // get max and min
            $maxStock = max($stocks);
            $minStock = min($stocks);

            if ($maxStock === $minStock) {
                // if max equals min, return single value
                return $maxStock;

            } else {
                // if max doesn't equal min, return both value with separator '～'
                return $minStock . trans('admin.common.separator__range') . $maxStock;
            }
        }
    }
    public function calculatePrice($productId)
    {
        $Product = $this->productRepository->find($productId);
        $ProductClasses = $Product->getProductClasses();

        // get ProductClass's price02 column value
        $prices = $ProductClasses->map(function ($ProductClass) {
            return intval($ProductClass->getprice02());
        })->toArray();

        // get max and min
        $maxPrice = max($prices);
        $minPrice = min($prices);

        if ($maxPrice === $minPrice) {
            // if max equals min, return single value
            return trans('collection.admin.collection.price').$minPrice;

        } else {
            // if max doesn't equal min, return both value with separator '～'
            return trans('collection.admin.collection.price').$minPrice . trans('admin.common.separator__range') . trans('collection.admin.collection.price').$maxPrice;
        }

    }
}
