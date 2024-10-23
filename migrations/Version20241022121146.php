<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241022121146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add grille_visite_filename column to territory';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory ADD grille_visite_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory DROP grille_visite_filename');
    }
}
