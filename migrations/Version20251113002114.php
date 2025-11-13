<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113002114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CC0990423
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CD22CABCD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match CHANGE player1_id player1_id INT NOT NULL, CHANGE player2_id player2_id INT NOT NULL, CHANGE round round INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CC0990423 FOREIGN KEY (player1_id) REFERENCES tournament_participant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CD22CABCD FOREIGN KEY (player2_id) REFERENCES tournament_participant (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CC0990423
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CD22CABCD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match CHANGE player1_id player1_id INT DEFAULT NULL, CHANGE player2_id player2_id INT DEFAULT NULL, CHANGE round round VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CC0990423 FOREIGN KEY (player1_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CD22CABCD FOREIGN KEY (player2_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
    }
}
