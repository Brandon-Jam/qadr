<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114122145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD loser_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551C1BCAA5F6 FOREIGN KEY (loser_id) REFERENCES tournament_participant (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BB0D551C1BCAA5F6 ON tournament_match (loser_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551C1BCAA5F6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BB0D551C1BCAA5F6 ON tournament_match
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP loser_id
        SQL);
    }
}
