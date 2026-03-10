<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240321041839 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE dtb_customer ADD is_supplier BOOLEAN DEFAULT FALSE NULL, ADD enter_supplier_code_date DATETIME NULL, ADD supplier_code VARCHAR(255) DEFAULT 'buzz'");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dtb_customer DROP is_supplier, DROP enter_supplier_code_date, DROP supplier_code');

    }
}
