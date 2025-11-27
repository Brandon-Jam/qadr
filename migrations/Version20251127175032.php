<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127175032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament ADD max_pending_slots INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees DROP FOREIGN KEY FK_63B406E133D1A3E7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees DROP FOREIGN KEY FK_63B406E1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees ADD CONSTRAINT FK_63B406E133D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees ADD CONSTRAINT FK_63B406E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant ADD is_pending TINYINT(1) NOT NULL, ADD is_approved TINYINT(1) NOT NULL, ADD is_paid TINYINT(1) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant DROP is_pending, DROP is_approved, DROP is_paid
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament DROP max_pending_slots
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees DROP FOREIGN KEY FK_63B406E133D1A3E7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees DROP FOREIGN KEY FK_63B406E1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees ADD CONSTRAINT FK_63B406E133D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_referees ADD CONSTRAINT FK_63B406E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
    }
}
