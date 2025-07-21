<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250721091306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Change suivi category from INTERVENTION_IS_CREATED to INTERVENTION_IS_REQUIRED if description is 'Aucune information de visite n'a été renseignée pour le logement. Merci de programmer une visite dès que possible !'";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_REQUIRED' WHERE category = 'INTERVENTION_IS_CREATED' AND description LIKE '%renseignée pour le logement. Merci de programmer une visite dès que possible !'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_CREATED' WHERE category = 'INTERVENTION_IS_REQUIRED' AND description LIKE '%renseignée pour le logement. Merci de programmer une visite dès que possible !'");
    }
}
