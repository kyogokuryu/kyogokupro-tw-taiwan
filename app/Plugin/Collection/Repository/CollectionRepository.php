<?php

namespace Plugin\Collection\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\Constant;
use Eccube\Doctrine\Query\Queries;
use Eccube\Util\StringUtil;
use Plugin\Collection\Entity\Collection;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Collection|null find($id, $lockMode = null, $lockVersion = null)
 * @method Collection|null findOneBy(array $criteria, array $orderBy = null)
 * @method Collection[]    findAll()
 * @method Collection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionRepository extends ServiceEntityRepository
{
    const COLLECTION_SEARCH_ADMIN = 'Collection.getQueryBuilderBySearchDataForAdmin';

    /**
     * @var Queries
     */
    protected $queries;

    /**
     * CollectionRepository constructor.
     *
     * @param RegistryInterface $registry
     * @param Queries $queries
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, Collection::class);
        $this->queries = $queries;
    }

    /**
     * @param array $searchData
     *
     * @return QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c');
        
        // select item what isn't softdeleted
        $qb
            ->andWhere('c.deleted = :deleted')
            ->setParameter('deleted', Constant::DISABLED);

        // collection_code and name
        if (isset($searchData['code_name']) && StringUtil::isNotBlank($searchData['code_name'])) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('c.collection_code', ':collection_code'),
                        $qb->expr()->like('c.name', ':name')
                    )
                )
                ->setParameter('collection_code', '%'.$searchData['code_name'].'%')
                ->setParameter('name', '%'.$searchData['code_name'].'%');
        }

        
        // visible
        if (isset($searchData['visible']) && $count = count($searchData['visible'])) {
            // 送信済/未送信両方にチェックされている場合は検索条件に追加しない
            if ($count < 2) {
                $qb
                    ->andWhere('c.visible = :visible')
                    ->setParameter('visible', current($searchData['visible']));
            }
        }

        /**
         * display_from and display_to
         * 
         * $searchData['display_from'] and $searchData['display_to'] are JST,
         * but c.display_from and c.display_to are UTC
         */
        if ((! empty($searchData['display_from']) && $searchData['display_from'])
            && (! empty($searchData['display_to']) && $searchData['display_to'])) {
            $displayFrom = $searchData['display_from']->modify('-9 hours')->format('Y-m-d H:i:s');
            $displayTo = $searchData['display_to']->modify('-9 hours')->format('Y-m-d H:i:s');
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('c.display_from'),
                        $qb->expr()->isNotNull('c.display_to'),
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->lt('c.display_from', "'{$displayFrom}'"),
                                $qb->expr()->lte("'{$displayFrom}'", 'c.display_to')
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->lte("'{$displayFrom}'", 'c.display_from'),
                                $qb->expr()->lte('c.display_to', "'{$displayTo}'")
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->lte('c.display_from', "'{$displayTo}'"),
                                $qb->expr()->lt("'{$displayTo}'", 'c.display_to')
                            )
                        )
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('c.display_from'),
                        $qb->expr()->isNull('c.display_to'),
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->lte("'{$displayFrom}'", 'c.display_from'),
                                $qb->expr()->lte('c.display_from', "'{$displayTo}'")
                            ),
                            $qb->expr()->lte('c.display_from', "'{$displayFrom}'")
                        )
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('c.display_from'),
                        $qb->expr()->isNotNull('c.display_to'),
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->lte("'{$displayFrom}'", 'c.display_to'),
                                $qb->expr()->lte('c.display_to', "'{$displayTo}'")
                            ),
                            $qb->expr()->lte("'{$displayTo}'", 'c.display_to')
                        )
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('c.display_from'),
                        $qb->expr()->isNull('c.display_to')
                    )
                ));

        } else if ((! empty($searchData['display_from']) && $searchData['display_from'])
            && empty($searchData['display_to'])) {
            $displayFrom = $searchData['display_from']->modify('-9 hours')->format('Y-m-d H:i:s');
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->isNotNull('c.display_from'),
                            $qb->expr()->isNotNull('c.display_to'),
                            $qb->expr()->orX(
                                $qb->expr()->andX(
                                    $qb->expr()->lte('c.display_from', "'{$displayFrom}'"),
                                    $qb->expr()->lte("'{$displayFrom}'", 'c.display_to')
                                ),
                                $qb->expr()->lte("'{$displayFrom}'", 'c.display_from')
                            )
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->isNotNull('c.display_from'),
                            $qb->expr()->isNull('c.display_to'),
                            $qb->expr()->lte("'{$displayFrom}'", 'c.display_from')
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->isNull('c.display_from'),
                            $qb->expr()->isNotNull('c.display_to'),
                            $qb->expr()->lte("'{$displayFrom}'", 'c.display_to')
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->isNull('c.display_from'),
                            $qb->expr()->isNull('c.display_to')
                        )
                    )
                );

        } else if ((! empty($searchData['display_to']) && $searchData['display_to'])
            && empty($searchData['display_from'])) {
            $displayTo = $searchData['display_to']->modify('-9 hours')->format('Y-m-d H:i:s');
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->isNotNull('c.display_from'),
                            $qb->expr()->isNotNull('c.display_to'),
                            $qb->expr()->orX(
                                $qb->expr()->andX(
                                    $qb->expr()->lte('c.display_from', "'{$displayTo}'"),
                                    $qb->expr()->lte("'{$displayTo}'", 'c.display_to')
                                ),
                                $qb->expr()->lte('c.display_to', "'{$displayTo}'")
                            )
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->isNotNull('c.display_from'),
                            $qb->expr()->isNull('c.display_to'),
                            $qb->expr()->lte('c.display_from', "'{$displayTo}'")
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->isNull('c.display_from'),
                            $qb->expr()->isNotNull('c.display_to'),
                            $qb->expr()->lte('c.display_to', "'{$displayTo}'")
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->isNull('c.display_from'),
                            $qb->expr()->isNull('c.display_to')
                        )
                    )
                );
        }

        // Order By
        $qb->orderBy('c.sort_no', 'DESC');

        return $this->queries->customize($this::COLLECTION_SEARCH_ADMIN, $qb, $searchData);
    }

    /**
     * soft delete
     * 
     * @param int|string|null $id
     */
    public function delete($id)
    {
        $Collection = $this->find($id);
        $Collection->setDeleted(Constant::ENABLED);
    }

    /**
     * validate if the collection_code is already registered
     * for UniqueEntity validation
     * 
     * @param array $criteria
     */
    public function getByCode(array $criteria)
    {
        return $this->findBy($criteria);
    }

    /**
     * get Collections
     * for block
     */
    public function getCollectionBlock()
    {
        $qb = $this->createQueryBuilder('c');

        $today = (new \DateTime('today'))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        // display_from and display_to
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('c.display_from'),
                        $qb->expr()->isNotNull('c.display_to'),
                        $qb->expr()->andX(
                            $qb->expr()->lte('c.display_from', "'{$today}'"),
                            $qb->expr()->lte("'{$today}'", 'c.display_to')
                        )
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('c.display_from'),
                        $qb->expr()->isNull('c.display_to'),
                        $qb->expr()->lte('c.display_from', "'{$today}'")
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('c.display_from'),
                        $qb->expr()->isNotNull('c.display_to'),
                        $qb->expr()->lte("'{$today}'", 'c.display_to')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('c.display_from'),
                        $qb->expr()->isNull('c.display_to')
                    )
                )
            );

        // visible
        $qb->andWhere('c.visible = true')
            ->andWhere('c.deleted = false');

        // sort by sort_no
        $qb->orderBy('c.sort_no', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getMaxSortNo()
    {
        $result = $this->createQueryBuilder('c')
            ->select('MAX(c.sort_no) as max')
            ->getQuery()
            ->getSingleScalarResult();

        return intval($result);
    }
}
