<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220905063111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `plg_video_categories` 
            (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `name` VARCHAR(255) NOT NULL ,
                `discriminator_type` VARCHAR(255) DEFAULT '0',
                `created_at`       DATETIME   NOT NULL,
                `updated_at`       DATETIME   NOT NULL, 
                 PRIMARY KEY (`id`),
                 INDEX (`id`)
            ) ENGINE = InnoDB;"
        );

        $this->addSql("CREATE TABLE `plg_videos`
            (
                `id`                INT          NOT NULL AUTO_INCREMENT,
                `video_category_id` INT          NOT NULL,
                `video_point_setting_id` INT    NULL ,
                `name`             VARCHAR(255) NOT NULL,
                `link`              TEXT         NOT NULL,
                `description`       TEXT         NULL, 
                `status`            TINYINT      NOT NULL DEFAULT '0' COMMENT '0: private, 1 public',
                `discriminator_type`  VARCHAR(255) NOT NULL DEFAULT 'video',
                `created_at`        DATETIME    NOT NULL,
                `updated_at`        DATETIME    NOT NULL,     
                PRIMARY KEY (`id`),
                INDEX (`id`),
                INDEX (`video_category_id`),
                INDEX (`video_point_setting_id`)
            ) ENGINE = InnoDB;"
        );

        $this->addSql("CREATE TABLE `plg_video_point_settings` 
            ( 
                `id`                  INT          NOT NULL AUTO_INCREMENT,
                `video_id`            INT          NOT NULL,
                `second`              INT          NOT NULL,
                `point`               INT          NOT NULL,
                `discriminator_type`  VARCHAR(255) DEFAULT 'videopointsetting',
                `created_at`          DATETIME     NOT NULL ,
                `updated_at`          DATETIME     NOT NULL,
                PRIMARY KEY (`id`),
                INDEX (`id`),
                INDEX (`video_id`)
            ) ENGINE = InnoDB;"
        );

        $this->addSql("CREATE TABLE `plg_video_watch_points` 
            ( 
                `id`                  INT          NOT NULL AUTO_INCREMENT,
                `customer_id`         INT          NOT NULL,
                `video_point_setting_id`    INT          NOT NULL,
                `video_id`               INT          NOT NULL,
                `discriminator_type`  VARCHAR(255) DEFAULT 'videowatchpoint',
                `created_at`          DATETIME     NOT NULL ,
                `updated_at`          DATETIME     NOT NULL,
                PRIMARY KEY (`id`),
                INDEX (`id`),
                INDEX (`video_id`),
                INDEX (`customer_id`),
                INDEX (`video_point_setting_id`)
            ) ENGINE = InnoDB;"
        );

        $this->addSql("CREATE TABLE `plg_video_relative_products` 
            ( 
                `id`                  INT          NOT NULL AUTO_INCREMENT,
                `product_id`          INT          NOT NULL,
                `video_id`            INT          NOT NULL,
                `discriminator_type`  VARCHAR(255) DEFAULT 'videorelativeproduct',
                `created_at`          DATETIME     NOT NULL ,
                `updated_at`          DATETIME     NOT NULL,
                PRIMARY KEY (`id`),
                INDEX (`id`),
                INDEX (`product_id`),
                INDEX (`video_id`)
            ) ENGINE = InnoDB;"
        );

    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE ` plg_videos`");
        $this->addSql("DROP TABLE ` plg_video_categories`");
        $this->addSql("DROP TABLE ` plg_video_point_settings`");
        $this->addSql("DROP TABLE ` plg_video_watch_points`");
        $this->addSql("DROP TABLE ` plg_video_relative_products`");
    }
}
