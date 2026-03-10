<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221116032401 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(
            "CREATE TABLE `plg_page_countdown_setting`
            (
                `id`                    INT             NOT NULL    AUTO_INCREMENT,
                `point`                 INT             NOT NULL,
                `times`                 INT             NOT NULL,
                `second`                INT             NOT NULL,
                `interval_time`         INT             NOT NULL,
                `discriminator_type`    VARCHAR(255)    NOT NULL DEFAULT 'page_countdowns',
                `created_at`            DATETIME        NOT NULL,
                `updated_at`            DATETIME        NOT NULL,     
                PRIMARY KEY (`id`),
                INDEX (`id`)
            ) ENGINE = InnoDB;"
        );

        $this->addSql(
            "CREATE TABLE `plg_page_countdown_reward`
            (
                `id`                    INT             NOT NULL    AUTO_INCREMENT,
                `customer_id`           INT             NOT NULL,
                `page_countdown_id`     INT             NOT NULL,
                `point`                 INT             NOT NULL,
                `second`                INT             NOT NULL,
                `discriminator_type`    VARCHAR(255)    NOT NULL DEFAULT 'page_countdowns_reward',
                `created_at`            DATETIME        NOT NULL,
                `updated_at`            DATETIME        NOT NULL,     
                PRIMARY KEY (`id`),
                INDEX (`id`),
                INDEX (`page_countdown_id`),
                INDEX (`customer_id`)
            ) ENGINE = InnoDB;"
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("DROP TABLE ` plg_page_countdown_setting`");
        $this->addSql("DROP TABLE ` plg_page_countdown_reward`");
        // this down() migration is auto-generated, please modify it to your needs
    }
}
