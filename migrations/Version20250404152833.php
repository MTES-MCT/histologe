<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250404152833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_mailing_summary to user and update notification table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_mailing_summary TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE notification ADD wait_mailing_summary TINYINT(1) NOT NULL, ADD mailing_summary_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD deleted TINYINT(1) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL COMMENT \'Value possible enum NotificationType\'');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA6D0ABA22');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA6D0ABA22 FOREIGN KEY (affectation_id) REFERENCES affectation (id) ON DELETE CASCADE');

        $this->addSql('UPDATE user SET is_mailing_summary = 1');
        $this->addSql('UPDATE notification SET type = \'NOUVEAU_SUIVI\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_mailing_summary');
        $this->addSql('ALTER TABLE notification DROP wait_mailing_summary, DROP mailing_summary_sent_at, DROP deleted, CHANGE type type INT NOT NULL');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA6D0ABA22');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA6D0ABA22 FOREIGN KEY (affectation_id) REFERENCES affectation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
