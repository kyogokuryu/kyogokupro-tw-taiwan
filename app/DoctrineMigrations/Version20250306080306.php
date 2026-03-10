<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250306080306 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(
            "ALTER TABLE `dtb_order`
            ADD `invoice_date`            DATETIME  NULL,
            ADD `invoice_number`          INT       NULL"
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE dtb_order DROP COLUMN `invoice_date`,`invoice_number`");
    }
}
