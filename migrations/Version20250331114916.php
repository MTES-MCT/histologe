<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250331114916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update admin name partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE partner SET nom = "Administrateurs Signal-logement" WHERE nom = "Administrateurs Histologe ALL"');
        $this->addSql('UPDATE user SET prenom = "Signal-logement", email = "admin@signal-logement.beta.gouv.fr" WHERE email = "admin@histologe.net"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE partner SET nom = "Administrateurs Histologe ALL" WHERE nom = "Administrateurs Signal-logement"');
        $this->addSql('UPDATE user SET prenom = "Histologe", email = "admin@histologe.net" WHERE email = "admin@signal-logement.beta.gouv.fr"');
    }
}
