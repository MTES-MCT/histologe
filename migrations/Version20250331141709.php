<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250331141709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add label for 2 DesordrePrecision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE desordre_precision SET label = "Le logement n\'est pas sous un toit" WHERE desordre_precision_slug = "desordres_batiment_isolation_dernier_etage_toit_sous_toit_non"');
        $this->addSql('UPDATE desordre_precision SET label = "Le logement est à un étage supérieur" WHERE desordre_precision_slug = "desordres_batiment_isolation_infiltration_eau_au_sol_non"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE desordre_precision SET label = "" WHERE desordre_precision_slug = "desordres_batiment_isolation_dernier_etage_toit_sous_toit_non"');
        $this->addSql('UPDATE desordre_precision SET label = "" WHERE desordre_precision_slug = "desordres_batiment_isolation_infiltration_eau_au_sol_non"');
    }
}
