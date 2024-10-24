<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241024083100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set consistent ville_occupant values for Marseille and Lyon';
    }

    public function up(Schema $schema): void
    {
        $mapping = [
            // Marseille
            'MARSEILLE' => 'Marseille',
            'MARSEILLE 2EME' => 'Marseille 2e Arrondissement',
            'MARSEILLE 02' => 'Marseille 2e Arrondissement',
            'MARSEILLE 04' => 'Marseille 4e Arrondissement',
            'MARSEILLE (13013)' => 'Marseille 13e Arrondissement',
            'MARSEILLE 14' => 'Marseille 14e Arrondissement',
            // Lyon
            'LYON 05' => 'Lyon 5e Arrondissement',
            'Lyon 6' => 'Lyon 6e Arrondissement',
            'LYON 08' => 'Lyon 8e Arrondissement',
            'LYON 9EME' => 'Lyon 9e Arrondissement',
        ];
        foreach ($mapping as $key => $value) {
            $this->addSql("UPDATE signalement SET ville_occupant = '".$value."' WHERE ville_occupant = '".$key."'");
        }
    }

    public function down(Schema $schema): void
    {
    }
}
