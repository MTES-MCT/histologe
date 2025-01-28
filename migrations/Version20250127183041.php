<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250127183041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update JSON fields with signalement column value.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE `signalement`
            SET type_composition_logement = JSON_SET(type_composition_logement, '$.type_logement_nature', nature_logement)
            WHERE JSON_VALID(type_composition_logement) AND nature_logement IS NOT NULL;
        ");

        $this->addSql("
            UPDATE `signalement`
            SET situation_foyer = JSON_SET(situation_foyer, '$.logement_social_date_naissance', DATE(date_naissance_occupant))
            WHERE JSON_VALID(situation_foyer) AND date_naissance_occupant IS NOT NULL;");

        $this->addSql("
            UPDATE `signalement`
            SET situation_foyer = JSON_SET(situation_foyer, '$.logement_social_numero_allocataire', num_allocataire)
            WHERE JSON_VALID(situation_foyer) AND num_allocataire IS NOT NULL;");

        $this->addSql("
            UPDATE `signalement`
            SET information_complementaire = JSON_SET(information_complementaire, '$.informations_complementaires_logement_montant_loyer', loyer)
            WHERE JSON_VALID(information_complementaire) AND loyer IS NOT NULL;");
    }

    public function down(Schema $schema): void
    {
    }
}
