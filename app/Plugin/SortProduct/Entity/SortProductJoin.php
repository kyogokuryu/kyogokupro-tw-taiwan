<?php
namespace Plugin\SortProduct\Entity;

use Eccube\Common\EccubeConfig;
use Eccube\Doctrine\Query\JoinClause;
use Eccube\Doctrine\Query\JoinCustomizer;
use Eccube\Entity\Master\ProductListOrderBy;
use Eccube\Repository\QueryKey;

/**
 * Class SortProductJoin
 * @package Plugin\SortProduct\Entity
 * @see Plugin\SortProduct\Resource\config\services.yml
 */
class SortProductJoin extends JoinCustomizer
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * ProductRankCustomizer constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function createStatements($params, $queryKey)
    {
        if (!isset($params['orderby']) || !$params['orderby'] instanceof ProductListOrderBy) {
            return [];
        }
        /** @var ProductListOrderBy $OrderBy */
        $OrderBy = $params['orderby'];
        if ($OrderBy->getName() != $this->eccubeConfig->get('sort_product.list_order_by.name')) {
            return [];
        }

        return [JoinClause::leftJoin('Plugin\SortProduct\Entity\SortProduct', 'sp', 'WITH', 'p.id = sp.product_id')];
    }

    public function getQueryKey()
    {
        return QueryKey::PRODUCT_SEARCH;
    }

}