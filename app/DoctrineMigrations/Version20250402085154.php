<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250402085154 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE dtb_order DROP COLUMN invoice_number');
        $this->addSql('ALTER TABLE dtb_order ADD invoice_number VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE dtb_order ADD invoice_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dtb_order DROP COLUMN invoice_number');
    }
}
