<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008125242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Synchronisation des entitées et de la strcture de la base';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation CHANGE answered_at answered_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE api_user_token CHANGE expires_at expires_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE arrete CHANGE date_arrete date_arrete DATE NOT NULL, CHANGE date_main_levee date_main_levee DATE DEFAULT NULL, CHANGE imported_at imported_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE auto_affectation_rule CHANGE procedures_suspectees procedures_suspectees LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE club_event CHANGE date_event date_event DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE critere CHANGE created_at created_at DATETIME NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE criticite CHANGE created_at created_at DATETIME NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE desordre_categorie CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE desordre_critere CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE desordre_precision CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE email_delivery_issue CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE failed_email CHANGE created_at created_at DATETIME NOT NULL, CHANGE last_attempt_at last_attempt_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE file CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE scanned_at scanned_at DATETIME DEFAULT NULL, CHANGE partner_competence partner_competence LONGTEXT DEFAULT NULL, CHANGE partner_type partner_type LONGTEXT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE date_prise_de_vue date_prise_de_vue DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE file RENAME INDEX fk_8c9f0d3e6e0b3dca TO IDX_8C9F361073F74AD4');
        $this->addSql('ALTER TABLE history_entry CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE intervention CHANGE scheduled_at scheduled_at DATETIME DEFAULT NULL, CHANGE registered_at registered_at DATETIME DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE conclude_procedure conclude_procedure TINYTEXT DEFAULT NULL, CHANGE reminder_before_sent_at reminder_before_sent_at DATETIME DEFAULT NULL, CHANGE reminder_conclusion_sent_at reminder_conclusion_sent_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE conclusion_visite_edited_at conclusion_visite_edited_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE job_event CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE notification CHANGE created_at created_at DATETIME NOT NULL, CHANGE mailing_summary_sent_at mailing_summary_sent_at DATETIME DEFAULT NULL, CHANGE seen_at seen_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_312B3E1673F74AD4 ON partner');
        $this->addSql('ALTER TABLE partner CHANGE uuid uuid CHAR(36) NOT NULL, CHANGE competence competence LONGTEXT DEFAULT NULL, CHANGE idoss_token_expiration_date idoss_token_expiration_date DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE pop_notification CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE service_secours_route CHANGE uuid uuid BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE signalement_draft CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE pending_draft_reminded_at pending_draft_reminded_at DATETIME DEFAULT NULL, CHANGE bailleur_prevenu_at bailleur_prevenu_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE situation CHANGE created_at created_at DATETIME NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE suivi CHANGE created_at created_at DATETIME NOT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE is_visible_for_bailleur is_visible_for_bailleur TINYINT NOT NULL');
        $this->addSql('ALTER TABLE suivi_delayed CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tiers_invitation CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE uuid uuid CHAR(36) NOT NULL, CHANGE token_expired_at token_expired_at DATETIME DEFAULT NULL, CHANGE last_login_at last_login_at DATETIME DEFAULT NULL, CHANGE archiving_scheduled_at archiving_scheduled_at DATE DEFAULT NULL, CHANGE anonymized_at anonymized_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE duplicate_modal_dismissed_at duplicate_modal_dismissed_at DATETIME DEFAULT NULL, CHANGE is_mailing_club_event is_mailing_club_event TINYINT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_partner CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_search_filter CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_search_name ON user_search_filter (user_id, name)');
        $this->addSql('ALTER TABLE user_search_filter RENAME INDEX idx_503adcbea76ed395 TO IDX_48A89D52A76ED395');
        $this->addSql('ALTER TABLE user_signalement_subscription CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE zone CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE area area GEOMETRY NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation CHANGE answered_at answered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE api_user_token CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE arrete CHANGE date_arrete date_arrete DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE date_main_levee date_main_levee DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE imported_at imported_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE auto_affectation_rule CHANGE procedures_suspectees procedures_suspectees LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE club_event CHANGE date_event date_event DATETIME NOT NULL');
        $this->addSql('ALTER TABLE critere CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE modified_at modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE criticite CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE modified_at modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE desordre_categorie CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE desordre_critere CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE desordre_precision CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE email_delivery_issue CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE failed_email CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_attempt_at last_attempt_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE file CHANGE description description VARCHAR(250) DEFAULT NULL, CHANGE date_prise_de_vue date_prise_de_vue DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE scanned_at scanned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE partner_competence partner_competence LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE partner_type partner_type LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE file RENAME INDEX idx_8c9f361073f74ad4 TO FK_8C9F0D3E6E0B3DCA');
        $this->addSql('ALTER TABLE history_entry CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE intervention CHANGE scheduled_at scheduled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE registered_at registered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE status status VARCHAR(255) NOT NULL, CHANGE conclude_procedure conclude_procedure TINYTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE conclusion_visite_edited_at conclusion_visite_edited_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE reminder_before_sent_at reminder_before_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE reminder_conclusion_sent_at reminder_conclusion_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE job_event CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE notification CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE mailing_summary_sent_at mailing_summary_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE seen_at seen_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE partner CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE competence competence LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE idoss_token_expiration_date idoss_token_expiration_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_312B3E1673F74AD4 ON partner (territory_id)');
        $this->addSql('ALTER TABLE pop_notification CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE service_secours_route CHANGE uuid uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE signalement_draft CHANGE bailleur_prevenu_at bailleur_prevenu_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE pending_draft_reminded_at pending_draft_reminded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE situation CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE modified_at modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE suivi CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_visible_for_bailleur is_visible_for_bailleur TINYINT DEFAULT 0 NOT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE suivi_delayed CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE tiers_invitation CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE token_expired_at token_expired_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_login_at last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_mailing_club_event is_mailing_club_event TINYINT DEFAULT 1 NOT NULL, CHANGE archiving_scheduled_at archiving_scheduled_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE anonymized_at anonymized_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE duplicate_modal_dismissed_at duplicate_modal_dismissed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_partner CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX uniq_user_search_name ON user_search_filter');
        $this->addSql('ALTER TABLE user_search_filter CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_search_filter RENAME INDEX idx_48a89d52a76ed395 TO IDX_503ADCBEA76ED395');
        $this->addSql('ALTER TABLE user_signalement_subscription CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
