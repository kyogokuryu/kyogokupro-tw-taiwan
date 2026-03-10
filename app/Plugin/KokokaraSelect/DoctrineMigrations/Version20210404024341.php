<?php declare(strict_types=1);
/**
 * Copyright(c) 2021 systemkd
 * Date: 2021/4/4
 */

namespace Plugin\KokokaraSelect\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Page;
use Eccube\Repository\PageRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210404024341 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {

        // ページファイル名更新
        $this->updatePageFileName('kokokara_select_list', 'KokokaraSelect/Resource/template/default/Product/list');
        $this->updatePageFileName('kokokara_select_list_edit', 'KokokaraSelect/Resource/template/default/Product/list');
    }

    private function updatePageFileName($key, $newFileName)
    {

        /** @var PageRepository $pageRepository */
        $pageRepository = $this->container->get(PageRepository::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        /** @var Page $page */
        $page = $pageRepository->findOneBy(['url' => $key]);

        if ($page) {

            $page->setFileName($newFileName);
            $entityManager->persist($page);
            $entityManager->flush();
        }
    }

    public function down(Schema $schema): void
    {
        // URL は変わらないため Version20200504012352 で削除
    }
}
