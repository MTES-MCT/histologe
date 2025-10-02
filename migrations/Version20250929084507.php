<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250929084507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set nom_declarant, prenom_declarant, tel_declarant, tel_declarant_secondaire, mail_declarant to null for profile LOCATAIRE or BAILLEUR_OCCUPANT on signalement V2';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE signalement
            SET
                nom_declarant = NULL,
                prenom_declarant = NULL,
                tel_declarant = NULL,
                tel_declarant_secondaire = NULL,
                mail_declarant = NULL
            WHERE profile_declarant IN ('LOCATAIRE', 'BAILLEUR_OCCUPANT') AND (created_by_id IS NOT NULL OR created_from_id IS NOT NULL)
");
    }

    public function down(Schema $schema): void
    {
    }
}
