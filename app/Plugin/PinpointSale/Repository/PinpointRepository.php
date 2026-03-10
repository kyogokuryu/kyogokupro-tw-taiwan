<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/03
 */

namespace Plugin\PinpointSale\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\ProductPinpoint;

class PinpointRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pinpoint::class);
    }

    /**
     * タイムセール（共通）を保存
     *
     * @param Pinpoint $pinpoint
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save($pinpoint)
    {
        if (!$pinpoint->getId()) {
            $sortNoTop = $this->findOneBy([], ['sortNo' => 'DESC']);
            $sort_no = 0;
            if (!is_null($sortNoTop)) {
                $sort_no = $sortNoTop->getSortNo();
            }

            $pinpoint->setSortNo($sort_no + 1);
        }

        $em = $this->getEntityManager();
        $em->persist($pinpoint);
        $em->flush($pinpoint);
    }

    /**
     * タイムセール（共通）を削除
     *
     * @param \Eccube\Entity\AbstractEntity $pinpoint
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($pinpoint)
    {
        $em = $this->getEntityManager();

        $em->beginTransaction();

        // 商品と共通設定のひも付き解除
        $qb = $em->createQueryBuilder()
            ->delete(ProductPinpoint::class, 'p')
            ->andWhere('p.Pinpoint = :pinpoint')
            ->setParameter('pinpoint', $pinpoint);
        $qb->getQuery()->execute();

        $this
            ->createQueryBuilder('p')
            ->update()
            ->set('p.sortNo', 'p.sortNo - 1')
            ->where('p.sortNo > :sortNo')
            ->setParameter('sortNo', $pinpoint->getSortNo())
            ->getQuery()
            ->execute();

        $em->remove($pinpoint);
        $em->flush($pinpoint);

        $em->commit();
    }

    /**
     * タイムセール設定一覧取得
     *
     * @param null $type 割引種類
     * @return mixed
     */
    public function getList($type = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.sortNo', 'DESC')
            ->addOrderBy('p.startTime', 'DESC');

        if ($type) {
            $qb->where('p.saleType = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }
}
