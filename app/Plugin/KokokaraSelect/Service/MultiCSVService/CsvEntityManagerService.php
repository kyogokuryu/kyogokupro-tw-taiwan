<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 *
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService;


use Doctrine\ORM\EntityManagerInterface;

class CsvEntityManagerService
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function rollback()
    {
        $this->entityManager->getConnection()->rollBack();
    }

    public function beginTransaction()
    {
        $this->entityManager->getConfiguration()->setSQLLogger(null);
        $this->entityManager->getConnection()->beginTransaction();
    }

    public function commit()
    {
        $this->entityManager->flush();
        $this->entityManager->getConnection()->commit();
    }

    public function update($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function remove($entity)
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    public function findEntity($entityClassName, $id)
    {
        return $this->entityManager->getRepository($entityClassName)->find($id);
    }

    public function findOneEntity($entityClassName, $args)
    {
        return $this->entityManager->getRepository($entityClassName)->findOneBy($args);
    }

    public function findAllQueryBuilder($entityClassName)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->from($entityClassName, 't')
            ->select('t');

        return $qb;
    }

    public function hasEntityManager()
    {
        return !is_null($this->entityManager);
    }

    public function detach($iterableResult)
    {
        $this->entityManager->detach($iterableResult);
    }

    /**
     * ログ出力停止
     */
    public function logStop()
    {
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);
    }
}
