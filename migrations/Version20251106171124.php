<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106171124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE tournament_referees (tournament_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_63B406E133D1A3E7 (tournament_id), INDEX IDX_63B406E1A76ED395 (user_id), PRIMARY KEY(tournament_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees ADD CONSTRAINT FK_63B406E133D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees ADD CONSTRAINT FK_63B406E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees DROP FOREIGN KEY FK_63B406E133D1A3E7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees DROP FOREIGN KEY FK_63B406E1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tournament_referees
        SQL);
    }
}
