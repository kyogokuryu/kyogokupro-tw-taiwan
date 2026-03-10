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
final class Version20200606041903 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    use ConfigMigrationTrait;

    public function initConfigData()
    {
        $this->configMigrationService->clearConfigData();

        $this->configMigrationService->addConfigParams(
            [
                [ConfigHelper::TYPE_STRING, ConfigSetting::SETTING_KEY_SELECT_ITEM_DEFAULT_NAME, '選択商品呼称', ConfigSetting::DEFAULT_SELECT_ITEM_NAME, ConfigSetting::SETTING_GROUP_COMMON, 1],
                [ConfigHelper::TYPE_STRING, ConfigSetting::SETTING_KEY_GROUP_DEFAULT_NAME, '未設定グループ名称', ConfigSetting::DEFAULT_GROUP_NAME, ConfigSetting::SETTING_GROUP_COMMON, 2],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_ADD_PRODUCT_REPEAT, '選択商品追加時の連続設定', false, ConfigSetting::SETTING_GROUP_COMMON, 3],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_SELECT_ITEM_VIEW_CART, '選択商品表示', true, ConfigSetting::SETTING_GROUP_CART, 1],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_SELECT_ITEM_VIEW_SHOPPING, '選択商品表示', true, ConfigSetting::SETTING_GROUP_SHOPPING, 1],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_SELECT_ITEM_VIEW_CONFIRM, '選択商品表示', true, ConfigSetting::SETTING_GROUP_CONFIRM, 1],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_SELECT_ITEM_VIEW_HISTORY, '選択商品表示', true, ConfigSetting::SETTING_GROUP_HISTORY, 1],
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
