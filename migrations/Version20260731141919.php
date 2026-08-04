<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731141919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add address_id to signalement and change unique index on address to include post_code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_address_housenumber_street_citycode ON address');
        $this->addSql('ALTER TABLE address CHANGE point point POINT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_address_housenumber_street_postcode_citycode ON address (housenumber, street, post_code, city_code)');
        $this->addSql('ALTER TABLE signalement ADD address_id INT DEFAULT NULL, CHANGE date_entree date_entree DATE DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL, CHANGE last_suivi_at last_suivi_at DATETIME DEFAULT NULL, CHANGE validated_at validated_at DATETIME DEFAULT NULL, CHANGE proprio_averti_at proprio_averti_at DATETIME DEFAULT NULL, CHANGE closed_at closed_at DATETIME DEFAULT NULL, CHANGE date_naissance_occupant date_naissance_occupant DATETIME DEFAULT NULL, CHANGE type_composition_logement type_composition_logement JSON DEFAULT NULL, CHANGE situation_foyer situation_foyer JSON DEFAULT NULL, CHANGE information_procedure information_procedure JSON DEFAULT NULL, CHANGE information_complementaire information_complementaire JSON DEFAULT NULL, CHANGE date_mission_service_secours date_mission_service_secours DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F4B55114F5B7AF75 ON signalement (address_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_address_housenumber_street_postcode_citycode ON address');
        $this->addSql('ALTER TABLE address CHANGE point point POINT DEFAULT NULL COMMENT \'(DC2Type:point)\'');
        $this->addSql('CREATE UNIQUE INDEX unique_address_housenumber_street_citycode ON address (housenumber, street, city_code)');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B55114F5B7AF75');
        $this->addSql('DROP INDEX IDX_F4B55114F5B7AF75 ON signalement');
        $this->addSql('ALTER TABLE signalement DROP address_id, CHANGE date_entree date_entree DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE modified_at modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_suivi_at last_suivi_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE validated_at validated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE proprio_averti_at proprio_averti_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE closed_at closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date_naissance_occupant date_naissance_occupant DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE type_composition_logement type_composition_logement JSON DEFAULT NULL COMMENT \'(DC2Type:type_composition_logement)\', CHANGE situation_foyer situation_foyer JSON DEFAULT NULL COMMENT \'(DC2Type:situation_foyer)\', CHANGE information_procedure information_procedure JSON DEFAULT NULL COMMENT \'(DC2Type:information_procedure)\', CHANGE information_complementaire information_complementaire JSON DEFAULT NULL COMMENT \'(DC2Type:information_complementaire)\', CHANGE date_mission_service_secours date_mission_service_secours DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE signalement_draft CHANGE bailleur_prevenu_at bailleur_prevenu_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE pending_draft_reminded_at pending_draft_reminded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
