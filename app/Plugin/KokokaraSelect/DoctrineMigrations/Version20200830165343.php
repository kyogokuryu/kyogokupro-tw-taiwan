<?php declare(strict_types=1);
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/06
 */

namespace Plugin\KokokaraSelect\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\KokokaraSelect\Config\ConfigSetting;
use Plugin\KokokaraSelect\Entity\PlgConfig;
use Plugin\KokokaraSelect\Repository\PlgConfigRepository;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigHelper;
use Plugin\KokokaraSelect\Service\PlgConfigService\DoctrineMigrations\ConfigMigrationTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200830165343 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    use ConfigMigrationTrait;

    public function initConfigData()
    {
        $this->configMigrationService->clearConfigData();

        $this->configMigrationService->addConfigParams(
            [
                [ConfigHelper::TYPE_STRING, ConfigSetting::SETTING_KEY_DIRECT_SELECT_ITEM_DEFAULT_NAME, '固定セット商品呼称', ConfigSetting::DEFAULT_DIRECT_SELECT_ITEM_NAME, ConfigSetting::SETTING_GROUP_COMMON, 3],
            ]
        );
    }

    public function up(Schema $schema)
    {

        // 既存indexの更新
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        /** @var PlgConfigRepository $plgConfigRepository */
        $plgConfigRepository = $entityManager->getRepository(PlgConfig::class);

        $sortUpList = [
            [
                'key' => ConfigSetting::SETTING_KEY_ADD_PRODUCT_REPEAT,
                'new_sort' => 4,
            ],
            [
                'key' => ConfigSetting::SETTING_KEY_CART_BUTTON_VIEW,
                'new_sort' => 5,
            ]
        ];

        // ソート番号更新
        foreach ($sortUpList as $item) {

            /** @var PlgConfig $config */
            $config = $plgConfigRepository->findOneBy(['configKey' => $item['key']]);

            $config->setSortNo($item['new_sort']);
            $entityManager->persist($config);
        }
        $entityManager->flush();

        $this->upMigration();
    }

    public function down(Schema $schema)
    {
        $this->downMigration();
    }
}
