<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260121140919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create column for ProfileOccupant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD profile_occupant VARCHAR(255) DEFAULT NULL COMMENT \'Value possible enum ProfileOccupant\'');

        // Set value to BAILLEUR_OCCUPANT for existing records where profile_declarant is BAILLEUR_OCCUPANT
        $this->addSql("UPDATE signalement
                       SET profile_occupant = '".ProfileOccupant::BAILLEUR_OCCUPANT->value."'
                       WHERE profile_declarant = '".ProfileDeclarant::BAILLEUR_OCCUPANT->value."'");

        // Set value to LOCATAIRE for existing records where profile_declarant is BAILLEUR or LOCATAIRE
        $this->addSql("UPDATE signalement
                       SET profile_occupant = '".ProfileOccupant::LOCATAIRE->value."'
                       WHERE profile_declarant IN ('".ProfileDeclarant::BAILLEUR->value."', '".ProfileDeclarant::LOCATAIRE->value."')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP profile_occupant');
    }
}
