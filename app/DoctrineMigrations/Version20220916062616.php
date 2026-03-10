<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220916062616 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $sql = "INSERT INTO dtb_page (page_name, url, file_name, edit_type, create_date, update_date, discriminator_type) VALUES (
            'よくあるご質問一覧',
            'faq',
            'Faq/index',
            2,
            NOW(),
            NOW(),
            'page'
        )";
        $this->addSql($sql);

        $sql = "INSERT INTO dtb_page_layout (page_id, layout_id, sort_no, discriminator_type) VALUES (
            (SELECT id FROM dtb_page WHERE page_name = 'よくあるご質問一覧'),
            2,
            (SELECT MAX(sort_no) FROM dtb_page_layout as pl) + 1,
            'pagelayout'
        )";
        $this->addSql($sql);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $sql = "DELETE FROM dtb_page_layout WHERE page_id = (SELECT id FROM dtb_page WHERE page_name = 'よくあるご質問一覧')";
        $this->addSql($sql);

        $sql = "DELETE FROM dtb_page WHERE page_name = 'よくあるご質問一覧'";
        $this->addSql($sql);
    }
}
