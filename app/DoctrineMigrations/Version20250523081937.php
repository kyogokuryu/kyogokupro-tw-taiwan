<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250523081937 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `dtb_product` ADD llmo_target_audience TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `dtb_product` ADD llmo_problem_solution TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `dtb_product` ADD llmo_usage_scene TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `dtb_product` ADD llmo_supervised_comment TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `dtb_product` ADD llmo_how_to_use TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `dtb_product` ADD llmo_features TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `dtb_product` ADD llmo_faq TEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `dtb_product` DROP llmo_target_audience');
        $this->addSql('ALTER TABLE `dtb_product` DROP llmo_problem_solution');
        $this->addSql('ALTER TABLE `dtb_product` DROP llmo_usage_scene');
        $this->addSql('ALTER TABLE `dtb_product` DROP llmo_supervised_comment');
        $this->addSql('ALTER TABLE `dtb_product` DROP llmo_how_to_use');
        $this->addSql('ALTER TABLE `dtb_product` DROP llmo_features');
        $this->addSql('ALTER TABLE `dtb_product` DROP llmo_faq');
    }
}
