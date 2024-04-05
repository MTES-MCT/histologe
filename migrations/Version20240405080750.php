<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240405080750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement s
        INNER JOIN signalement_draft d ON s.created_from_id = d.id
        SET s.ville_proprio = d.payload->"$.coordonnees_bailleur_adresse_detail_commune"
        WHERE s.code_postal_proprio IS NOT NULL AND s.ville_proprio IS NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
