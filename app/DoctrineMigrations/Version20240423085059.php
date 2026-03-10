<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240423085059 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            "CREATE TABLE `dtb_brands`
            (
                `id`                    INT             NOT NULL    AUTO_INCREMENT,
                `name`                  VARCHAR(255)    NOT NULL,
                `sort_no`               SMALLINT        NOT NULL    DEFAULT 0,
                `is_hidden`             INT             DEFAULT 0,
                `discriminator_type`    VARCHAR(255)    NOT NULL,
                `created_at`            DATETIME        NOT NULL,
                `updated_at`            DATETIME        NOT NULL,     
                PRIMARY KEY (`id`),
                INDEX (`id`),
                INDEX (`sort_no`)
            ) ENGINE = InnoDB;"
        );
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE dtb_brands');
    }
}
