<?php

namespace Plugin\Collection\Repository;

use Plugin\Collection\Entity\CollectionProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CollectionProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method CollectionProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method CollectionProduct[]    findAll()
 * @method CollectionProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionProductRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CollectionProduct::class);
    }

    // /**
    //  * @return CollectionProduct[] Returns an array of CollectionProduct objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CollectionProduct
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
