<?php declare(strict_types=1);
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/09
 */

namespace Plugin\KokokaraSelect\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Plugin\KokokaraSelect\Entity\KsOrderItemTypeEx;
use Plugin\KokokaraSelect\Repository\KsOrderItemTypeExRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200509015308 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    private const KEY_WORD = '選択商品[KokokaraSelect]';

    public function up(Schema $schema): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        // orderItemType取得
        /** @var OrderItemTypeRepository $orderItemTypeRepository */
        $orderItemTypeRepository = $this->container->get(OrderItemTypeRepository::class);

        $orderItemTypes = $orderItemTypeRepository->findBy([], ['id' => 'desc']);

        $maxId = 0;
        $maxSortNo = 0;

        $targetOrderItemType = null;

        /** @var OrderItemType $orderItemType */
        foreach ($orderItemTypes as $orderItemType) {
            if ($orderItemType->getName() == self::KEY_WORD) {
                $isMasterData = true;
                $targetOrderItemType = $orderItemType;
            }
        }

        if ($targetOrderItemType) {
            $maxId = $targetOrderItemType->getId();
            $maxSortNo += $targetOrderItemType->getSortNo();

        } else {

            /** @var OrderItemType $orderItemType */
            foreach ($orderItemTypes as $orderItemType) {
                $maxId = $orderItemType->getId();
                $maxSortNo = $orderItemType->getSortNo();
                break;
            }

            $maxId += 1;
            $maxSortNo += 1;

            $newOrderItemType = new OrderItemType();
            $newOrderItemType
                ->setId($maxId)
                ->setSortNo($maxSortNo)
                ->setName('選択商品[KokokaraSelect]');

            $entityManager->persist($newOrderItemType);
            $entityManager->flush($newOrderItemType);
        }

        $ksOrderItemTypeEx = new KsOrderItemTypeEx();
        $ksOrderItemTypeEx
            ->setKokokaraSelect(true)
            ->setOrderItemTypeId($maxId)
            ->setOrderItemTypeSortNo($maxSortNo);

        $entityManager->persist($ksOrderItemTypeEx);
        $entityManager->flush($ksOrderItemTypeEx);

    }

    public function down(Schema $schema): void
    {
        // マスタデータは削除不可

        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        /** @var KsOrderItemTypeExRepository $ksOrderItemTypeExRepository */
        $ksOrderItemTypeExRepository = $this->container->get(KsOrderItemTypeExRepository::class);

        foreach ($ksOrderItemTypeExRepository->findAll() as $item) {
            $entityManager->remove($item);
            $entityManager->flush();
        }
    }
}
