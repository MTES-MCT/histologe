<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250611102552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set production database consistent with local env.';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf('histologe' !== getenv('APP_ENV'), 'This migration should only be run in the histologe environment.');

        $this->addSql(<<<'SQL'
            UPDATE signalement SET score_logement = 0 WHERE score_logement IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE signalement SET score_batiment = 0 WHERE score_batiment IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job DROP FOREIGN KEY FK_FBD8E0F8A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE job
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE failed_email CHANGE subject subject LONGTEXT DEFAULT NULL, CHANGE notify_usager notify_usager TINYINT(1) NOT NULL, CHANGE error_message error_message LONGTEXT DEFAULT NULL, CHANGE is_resend_successful is_resend_successful TINYINT(1) NOT NULL, CHANGE retry_count retry_count INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file CHANGE extension extension VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_event CHANGE service service VARCHAR(255) NOT NULL, CHANGE action action VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE partner CHANGE insee insee JSON NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement CHANGE json_content json_content JSON NOT NULL, CHANGE is_usager_abandon_procedure is_usager_abandon_procedure TINYINT(1) DEFAULT NULL, CHANGE date_naissance_occupant date_naissance_occupant DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE score_logement score_logement DOUBLE PRECISION NOT NULL, CHANGE score_batiment score_batiment DOUBLE PRECISION NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE territory CHANGE bbox bbox JSON NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE is_activate_account_notification_enabled is_activate_account_notification_enabled TINYINT(1) NOT NULL, CHANGE archiving_scheduled_at archiving_scheduled_at DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', CHANGE has_permission_affectation has_permission_affectation TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE available_at available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, INDEX IDX_FBD8E0F8A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE failed_email CHANGE subject subject TEXT NOT NULL, CHANGE notify_usager notify_usager TINYINT(1) DEFAULT 0 NOT NULL, CHANGE error_message error_message TEXT DEFAULT NULL, CHANGE is_resend_successful is_resend_successful TINYINT(1) DEFAULT 0 NOT NULL, CHANGE retry_count retry_count INT DEFAULT 0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file CHANGE extension extension VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_event CHANGE service service VARCHAR(255) DEFAULT NULL, CHANGE action action VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE partner CHANGE insee insee LONGTEXT NOT NULL COLLATE `utf8mb4_bin`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement CHANGE json_content json_content LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE score_logement score_logement DOUBLE PRECISION DEFAULT NULL, CHANGE score_batiment score_batiment DOUBLE PRECISION DEFAULT NULL, CHANGE is_usager_abandon_procedure is_usager_abandon_procedure TINYINT(1) DEFAULT 0, CHANGE date_naissance_occupant date_naissance_occupant DATE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE territory CHANGE bbox bbox LONGTEXT NOT NULL COLLATE `utf8mb4_bin`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE has_permission_affectation has_permission_affectation TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_activate_account_notification_enabled is_activate_account_notification_enabled TINYINT(1) DEFAULT 1 NOT NULL, CHANGE archiving_scheduled_at archiving_scheduled_at DATE DEFAULT NULL
        SQL);
    }
}
