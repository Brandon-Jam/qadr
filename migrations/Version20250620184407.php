<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250620184407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE card (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, effect VARCHAR(255) NOT NULL, cost INT NOT NULL, power INT NOT NULL, image VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE match_card_play (id INT AUTO_INCREMENT NOT NULL, match_id INT DEFAULT NULL, player_id INT DEFAULT NULL, card_id INT DEFAULT NULL, used_by_id INT NOT NULL, played_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', effect_applied VARCHAR(255) DEFAULT NULL, used_at DATETIME NOT NULL, INDEX IDX_AB0B90B32ABEACD6 (match_id), INDEX IDX_AB0B90B399E6F5DF (player_id), INDEX IDX_AB0B90B34ACC9A20 (card_id), INDEX IDX_AB0B90B34C2B72A8 (used_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tournament (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, available_slots INT NOT NULL, price DOUBLE PRECISION NOT NULL, winning_price DOUBLE PRECISION NOT NULL, date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tournament_card (id INT AUTO_INCREMENT NOT NULL, tournament_id INT DEFAULT NULL, card_id INT NOT NULL, INDEX IDX_21992AED33D1A3E7 (tournament_id), INDEX IDX_21992AED4ACC9A20 (card_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tournament_match (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, player1_id INT DEFAULT NULL, player2_id INT DEFAULT NULL, winner_id INT DEFAULT NULL, score1 INT DEFAULT NULL, score2 INT DEFAULT NULL, round VARCHAR(255) NOT NULL, start_time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_BB0D551C33D1A3E7 (tournament_id), INDEX IDX_BB0D551CC0990423 (player1_id), INDEX IDX_BB0D551CD22CABCD (player2_id), INDEX IDX_BB0D551C5DFCD4B8 (winner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tournament_participant (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, tournament_id INT DEFAULT NULL, confirmed TINYINT(1) NOT NULL, joined_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', credits INT DEFAULT 10 NOT NULL, INDEX IDX_5C4BB35BA76ED395 (user_id), INDEX IDX_5C4BB35B33D1A3E7 (tournament_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tournament_participant_card (id INT AUTO_INCREMENT NOT NULL, participant_id INT NOT NULL, card_id INT NOT NULL, quantity INT DEFAULT NULL, INDEX IDX_61F4871F9D1C3019 (participant_id), INDEX IDX_61F4871F4ACC9A20 (card_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_card_play ADD CONSTRAINT FK_AB0B90B32ABEACD6 FOREIGN KEY (match_id) REFERENCES tournament_match (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_card_play ADD CONSTRAINT FK_AB0B90B399E6F5DF FOREIGN KEY (player_id) REFERENCES tournament_participant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_card_play ADD CONSTRAINT FK_AB0B90B34ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_card_play ADD CONSTRAINT FK_AB0B90B34C2B72A8 FOREIGN KEY (used_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_card ADD CONSTRAINT FK_21992AED33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_card ADD CONSTRAINT FK_21992AED4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551C33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CC0990423 FOREIGN KEY (player1_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CD22CABCD FOREIGN KEY (player2_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551C5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant ADD CONSTRAINT FK_5C4BB35BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant ADD CONSTRAINT FK_5C4BB35B33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant_card ADD CONSTRAINT FK_61F4871F9D1C3019 FOREIGN KEY (participant_id) REFERENCES tournament_participant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant_card ADD CONSTRAINT FK_61F4871F4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE match_card_play DROP FOREIGN KEY FK_AB0B90B32ABEACD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_card_play DROP FOREIGN KEY FK_AB0B90B399E6F5DF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_card_play DROP FOREIGN KEY FK_AB0B90B34ACC9A20
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE match_card_play DROP FOREIGN KEY FK_AB0B90B34C2B72A8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_card DROP FOREIGN KEY FK_21992AED33D1A3E7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_card DROP FOREIGN KEY FK_21992AED4ACC9A20
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551C33D1A3E7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CC0990423
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CD22CABCD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551C5DFCD4B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant DROP FOREIGN KEY FK_5C4BB35BA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant DROP FOREIGN KEY FK_5C4BB35B33D1A3E7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant_card DROP FOREIGN KEY FK_61F4871F9D1C3019
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tournament_participant_card DROP FOREIGN KEY FK_61F4871F4ACC9A20
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE card
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE match_card_play
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tournament
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tournament_card
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tournament_match
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tournament_participant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tournament_participant_card
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
