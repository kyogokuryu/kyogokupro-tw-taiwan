<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221014052345 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE `plg_videos` MODIFY video_category_id int null ");

    }

    public function down(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE `plg_videos` MODIFY video_category_id NOT NULL ");
    }
}
