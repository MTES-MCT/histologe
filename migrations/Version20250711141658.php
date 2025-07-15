<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250711141658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create UserSignalementSubscription table';
    }

    public function up(Schema $schema): void
    {
        // set affectation statuses consistent for archived signalements
        $this->addSql(<<<'SQL'
            UPDATE affectation SET statut = 'CLOSED' WHERE signalement_id IN (SELECT id FROM signalement WHERE statut = 'ARCHIVED')
        SQL);
        // create user_signalement_subscription table
        $this->addSql(<<<'SQL'
            CREATE TABLE user_signalement_subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, signalement_id INT NOT NULL, created_by_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_legacy TINYINT(1) NOT NULL, INDEX IDX_15392BCA76ED395 (user_id), INDEX IDX_15392BC65C5E57E (signalement_id), INDEX IDX_15392BCB03A8386 (created_by_id), UNIQUE INDEX unique_user_signalement_subscription (user_id, signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_signalement_subscription ADD CONSTRAINT FK_15392BCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_signalement_subscription ADD CONSTRAINT FK_15392BC65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_signalement_subscription ADD CONSTRAINT FK_15392BCB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_signalement_subscription DROP FOREIGN KEY FK_15392BCA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_signalement_subscription DROP FOREIGN KEY FK_15392BC65C5E57E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_signalement_subscription DROP FOREIGN KEY FK_15392BCB03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_signalement_subscription
        SQL);
    }
}
