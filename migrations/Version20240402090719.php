<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240402090719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update some signalements with is_allocataire from 0 or 1 to Oui or Non';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE signalement SET is_allocataire = 0 WHERE is_allocataire LIKE :allocataire_non',
            ['allocataire_non' => 'Non']
        );

        $this->addSql(
            'UPDATE signalement SET is_allocataire = 1 WHERE is_allocataire LIKE :allocataire_oui',
            ['allocataire_oui' => 'Oui']
        );
    }

    public function down(Schema $schema): void
    {
    }
}
