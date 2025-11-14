<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114120157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551C5DFCD4B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551C5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES tournament_participant (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551C5DFCD4B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551C5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
    }
}
