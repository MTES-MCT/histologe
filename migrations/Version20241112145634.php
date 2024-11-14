<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241112145634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for storing failed emails for retries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE failed_email (
                id INT AUTO_INCREMENT NOT NULL,
                type VARCHAR(50) NOT NULL,
                to_email JSON NOT NULL,
                from_email VARCHAR(255) DEFAULT NULL,
                from_fullname VARCHAR(255) DEFAULT NULL,
                params JSON NOT NULL,
                message TEXT DEFAULT NULL,
                territory_id INT DEFAULT NULL,
                user_id INT DEFAULT NULL,
                signalement_id INT DEFAULT NULL,
                signalement_draft_id INT DEFAULT NULL,
                suivi_id INT DEFAULT NULL,
                intervention_id INT DEFAULT NULL,
                previous_visite_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                attachment JSON DEFAULT NULL,
                motif VARCHAR(255) DEFAULT NULL,
                cron_label VARCHAR(255) DEFAULT NULL,
                cron_count INT DEFAULT NULL,
                notify_usager TINYINT(1) DEFAULT 0 NOT NULL,
                error_message TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                is_resend_successful TINYINT(1) DEFAULT 0 NOT NULL,
                retry_count INT DEFAULT 0,
                last_attempt_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id),
                CONSTRAINT FK_failed_email_territory_id FOREIGN KEY (territory_id) REFERENCES territory (id),
                CONSTRAINT FK_failed_email_user_id FOREIGN KEY (user_id) REFERENCES user (id),
                CONSTRAINT FK_failed_email_signalement_id FOREIGN KEY (signalement_id) REFERENCES signalement (id),
                CONSTRAINT FK_failed_email_suivi_id FOREIGN KEY (suivi_id) REFERENCES suivi (id),
                CONSTRAINT FK_failed_email_signalement_draft_id FOREIGN KEY (signalement_draft_id) REFERENCES signalement_draft (id),
                CONSTRAINT FK_failed_email_intervention_id FOREIGN KEY (intervention_id) REFERENCES intervention (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE failed_emails');
    }
}
