<?php declare(strict_types=1);

namespace Plugin\PinpointSale\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Eccube\Entity\Master\TaxType;
use Plugin\PinpointSale\Config\ConfigSetting;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigHelper;
use Plugin\PinpointSale\Service\PlgConfigService\DoctrineMigrations\ConfigMigrationTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190825075940 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    use ConfigMigrationTrait;

    public function initConfigData()
    {
        $this->configMigrationService->clearConfigData();

        $this->configMigrationService->addConfigParams(
            [
                [ConfigHelper::TYPE_CHOICE, ConfigSetting::SETTING_KEY_RATE_TYPE, '割引率端数処理', ConfigSetting::DISCOUNT_RATE_TYPE_ROUND, ConfigSetting::SETTING_GROUP_COMMON, 1],
                [ConfigHelper::TYPE_CHOICE, ConfigSetting::SETTING_KEY_DISCOUNT_TAX, '値引レコード課税区分', TaxType::TAXATION, ConfigSetting::SETTING_GROUP_COMMON, 2],
                [ConfigHelper::TYPE_STRING, ConfigSetting::SETTING_KEY_DISCOUNT_NAME, 'タイムセール値引名称', "タイムセール値引", ConfigSetting::SETTING_GROUP_COMMON, 3],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_PRODUCE_DETAIL_VIEW, 'タイムセール適用表示', true, ConfigSetting::SETTING_GROUP_PRODUCT_DETAIL, 1],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_PRODUCT_DETAIL_JS, '表示制御JavaScript追加', true, ConfigSetting::SETTING_GROUP_PRODUCT_DETAIL, 2],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_CART_VIEW, 'タイムセール適用表示', true, ConfigSetting::SETTING_GROUP_CART, 1],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_SHOPPING_VIEW, 'タイムセール適用表示', true, ConfigSetting::SETTING_GROUP_SHOPPING, 1],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_CONFIRM_VIEW, 'タイムセール適用表示', true, ConfigSetting::SETTING_GROUP_CONFIRM, 1],
                [ConfigHelper::TYPE_BOOL, ConfigSetting::SETTING_KEY_HISTORY_VIEW, 'タイムセール適用表示', true, ConfigSetting::SETTING_GROUP_HISTORY, 1],
            ]
        );
    }

    /**
     * Up
     *
     * @param Schema $schema
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function up(Schema $schema)
    {
        $this->upMigration();;
    }

    /**
     * Down
     *
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->downMigration();
    }
}
