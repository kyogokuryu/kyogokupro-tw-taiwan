<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/11
 */

namespace Plugin\KokokaraSelect\Doctrine;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Repository\QueryKey;
use Plugin\KokokaraSelect\Entity\KsProductOption;
use Plugin\KokokaraSelect\Repository\KsProductOptionRepository;

class ProductSearchCustomizer implements QueryCustomizer
{

    protected $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function customize(QueryBuilder $builder, $params, $queryKey)
    {

        /** @var KsProductOptionRepository $ksProductOptionRepository */
        $ksProductOptionRepository = $this->entityManager->getRepository(KsProductOption::class);

        $subQuery = $ksProductOptionRepository->getExistsSubQueryBuilder();

        $builder
            ->andWhere($builder->expr()->not($builder->expr()->exists($subQuery->getDQL())));

    }

    public function getQueryKey()
    {
        return QueryKey::PRODUCT_SEARCH;
    }
}
