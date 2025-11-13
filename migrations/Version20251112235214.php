<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112235214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE match_invite (id INT AUTO_INCREMENT NOT NULL, challenger_id INT NOT NULL, opponent_id INT NOT NULL, tournament_id INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_668DE9D62D521FDF (challenger_id), INDEX IDX_668DE9D67F656CDC (opponent_id), INDEX IDX_668DE9D633D1A3E7 (tournament_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_invite ADD CONSTRAINT FK_668DE9D62D521FDF FOREIGN KEY (challenger_id) REFERENCES tournament_participant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_invite ADD CONSTRAINT FK_668DE9D67F656CDC FOREIGN KEY (opponent_id) REFERENCES tournament_participant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_invite ADD CONSTRAINT FK_668DE9D633D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE match_invite DROP FOREIGN KEY FK_668DE9D62D521FDF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_invite DROP FOREIGN KEY FK_668DE9D67F656CDC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_invite DROP FOREIGN KEY FK_668DE9D633D1A3E7
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE match_invite
        SQL);
    }
}
