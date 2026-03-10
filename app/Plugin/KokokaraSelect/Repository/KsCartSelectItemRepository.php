<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\KokokaraSelect\Entity\KsCartSelectItem;

class KsCartSelectItemRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KsCartSelectItem::class);
    }

    /**
     * カート保持情報から削除された選択商品を削除
     *
     * @param $ksSelectItems
     */
    public function deleteByKsSelectItem($ksSelectItems)
    {

        $qb = $this->createQueryBuilder('c');

        $qb
            ->delete()
            ->where($qb->expr()->in('c.KsSelectItem', ':ksSelectItems'))
            ->setParameter('ksSelectItems', $ksSelectItems)
            ->getQuery()
            ->execute();
    }
}
