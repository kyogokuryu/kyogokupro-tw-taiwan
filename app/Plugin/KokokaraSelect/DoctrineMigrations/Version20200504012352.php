<?php declare(strict_types=1);
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PageRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200504012352 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    private const PAGE_URL = "kokokara_select_list";

    private function getTargetData()
    {
        return [
            [
                'name' => '[KokokaraSelect]商品選択',
                'url' => 'kokokara_select_list',
                'fileName' => '@KokokaraSelect/default/Product/list',
            ],
            [
                'name' => '[KokokaraSelect]商品選択(編集)',
                'url' => 'kokokara_select_list_edit',
                'fileName' => '@KokokaraSelect/default/Product/list',
            ],
        ];
    }

    public function up(Schema $schema): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $getTargetData = $this->getTargetData();

        // ページ情報追加
        foreach ($getTargetData as $getTargetDatum) {

            // 商品選択ページ追加
            $page = new Page();
            $page
                ->setName($getTargetDatum['name'])
                ->setUrl($getTargetDatum['url'])
                ->setFileName($getTargetDatum['fileName'])
                ->setEditType(Page::EDIT_TYPE_DEFAULT);

            $entityManager->persist($page);
            $entityManager->flush($page);

            /** @var LayoutRepository $layoutRepository */
            $layoutRepository = $this->container->get(LayoutRepository::class);

            /** @var Layout $layout */
            $layout = $layoutRepository->find(2);

            if ($layout) {

                /** @var PageLayoutRepository $pageLayoutRepository */
                $pageLayoutRepository = $this->container->get(PageLayoutRepository::class);
                $pageLayouts = $pageLayoutRepository->findBy(['layout_id' => $layout->getId()], ['sort_no' => 'desc']);
                $sortNo = 1;
                /** @var PageLayout $pageLayout */
                foreach ($pageLayouts as $pageLayout) {
                    $sortNo = $pageLayout->getSortNo();
                    $sortNo += 1;
                    break;
                }

                $pageLayout = new PageLayout();
                $pageLayout
                    ->setPageId($page->getId())
                    ->setPage($page)
                    ->setLayoutId($layout->getId())
                    ->setLayout($layout)
                    ->setSortNo($sortNo);

                $entityManager->persist($pageLayout);
                $entityManager->flush($pageLayout);
            }
        }

    }

    public function down(Schema $schema): void
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = $this->container->get(PageRepository::class);

        /** @var PageLayoutRepository $pageLayoutRepository */
        $pageLayoutRepository = $this->container->get(PageLayoutRepository::class);

        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $targetData = $this->getTargetData();

        foreach ($targetData as $targetDatum) {

            /** @var Page $page */
            $page = $pageRepository->findOneBy(['url' => $targetDatum['url']]);

            if ($page) {
                /** @var PageLayout $pageLayout */
                $pageLayouts = $pageLayoutRepository->findBy(['page_id' => $page->getId()]);

                foreach ($pageLayouts as $pageLayout) {
                    $entityManager->remove($pageLayout);
                }

                $entityManager->remove($page);
                $entityManager->flush();
            }

        }

    }
}
