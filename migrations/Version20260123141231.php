<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260123141231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change suivi category from INTERVENTION_IS_CREATED to INTERVENTION_IS_DONE, INTERVENTION_CONTROLE_IS_CREATED, INTERVENTION_CONTROLE_IS_DONE or INTERVENTION_ARRETE_IS_CREATED depending on the description and from INTERVENTION_IS_RESCHEDULED to INTERVENTION_CONTROLE_IS_RESCHEDULED or INTERVENTION_ARRETE_IS_RESCHEDULED depending on the description';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_DONE' WHERE category = 'INTERVENTION_IS_CREATED' AND description LIKE 'Visite réalisée : une visite du logement%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_CONTROLE_IS_CREATED' WHERE category = 'INTERVENTION_IS_CREATED' AND description LIKE 'Visite de contrôle programmée : une visite de contrôle du logement%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_CONTROLE_IS_DONE' WHERE category = 'INTERVENTION_IS_CREATED' AND description LIKE 'Visite de contrôle réalisée : une visite de contrôle du logement%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_ARRETE_IS_CREATED' WHERE category = 'INTERVENTION_IS_CREATED' AND description LIKE 'Un arrêté de mainlevée%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_ARRETE_IS_CREATED' WHERE category = 'INTERVENTION_IS_CREATED' AND description LIKE '%Type arrêté: %'");

        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_ARRETE_IS_RESCHEDULED' WHERE category = 'INTERVENTION_IS_RESCHEDULED' AND description LIKE 'Un arrêté de mainlevée%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_ARRETE_IS_RESCHEDULED' WHERE category = 'INTERVENTION_IS_RESCHEDULED' AND description LIKE '%Type arrêté: %'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_CONTROLE_IS_RESCHEDULED' WHERE category = 'INTERVENTION_IS_RESCHEDULED' AND description LIKE 'La date de visite de contrôle dans SI-Santé Habitat (SI-SH) a été modifiée%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_CREATED' WHERE category = 'INTERVENTION_IS_DONE' AND description LIKE 'Visite réalisée : une visite du logement%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_CREATED' WHERE category = 'INTERVENTION_CONTROLE_IS_CREATED' AND description LIKE 'Visite de contrôle programmée : une visite de contrôle du logement%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_CREATED' WHERE category = 'INTERVENTION_CONTROLE_IS_DONE' AND description LIKE 'Visite de contrôle réalisée : une visite de contrôle du logement%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_CREATED' WHERE category = 'INTERVENTION_ARRETE_IS_CREATED' AND description LIKE 'Un arrêté de mainlevée%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_CREATED' WHERE category = 'INTERVENTION_ARRETE_IS_CREATED' AND description LIKE '%Type arrêté: %'");

        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_RESCHEDULED' WHERE category = 'INTERVENTION_ARRETE_IS_RESCHEDULED' AND description LIKE 'Un arrêté de mainlevée%'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_RESCHEDULED' WHERE category = 'INTERVENTION_ARRETE_IS_RESCHEDULED' AND description LIKE '%Type arrêté: %'");
        $this->addSql("UPDATE suivi SET category = 'INTERVENTION_IS_RESCHEDULED' WHERE category = 'INTERVENTION_CONTROLE_IS_RESCHEDULED' AND description LIKE 'La date de visite de contrôle dans SI-Santé Habitat (SI-SH) a été modifiée%'");
    }
}
