<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241223080622 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE dtb_mlog ADD is_ceo INT DEFAULT 0');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE dtb_mlog DROP COLUMN is_ceo');
    }
}
