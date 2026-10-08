<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616101451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update description in suivi table for INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET description = '' WHERE category = 'INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE suivi SET description = :description WHERE category = 'INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR'",
            ['description' => 'Demande de clôture du dossier : les travaux ont été réalisés.']
        );
    }
}
