<?php declare(strict_types=1);
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/06
 */

namespace Plugin\KokokaraSelect\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Plugin\KokokaraSelect\Config\ConfigSetting;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigHelper;
use Plugin\KokokaraSelect\Service\PlgConfigService\DoctrineMigrations\ConfigMigrationTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200814205731 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    use ConfigMigrationTrait;

    public function initConfigData()
    {
        $this->configMigrationService->clearConfigData();

        $this->configMigrationService->addConfigParams(
            [
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_CART_BUTTON_VIEW, '商品を選択するボタンの表示', false, ConfigSetting::SETTING_GROUP_COMMON, 4],
            ]
        );
    }

    public function up(Schema $schema)
    {
        $this->upMigration();
    }

    public function down(Schema $schema)
    {
        $this->downMigration();
    }
}
