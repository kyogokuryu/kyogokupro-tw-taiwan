<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240521031903 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        
        // this up() migration is auto-generated, please modify it to your needs
    
        $this->addSql(
            "ALTER TABLE `dtb_brands`
            ADD `description`            TEXT,
            ADD `image`                  VARCHAR(255)"
        );
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE dtb_product DROP COLUMN `description`,`image`");
    }
}
