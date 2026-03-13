<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311081753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs syndic dans le signalement pour le service secours';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD denomination_syndic VARCHAR(255) DEFAULT NULL, ADD nom_syndic VARCHAR(255) DEFAULT NULL, ADD mail_syndic VARCHAR(255) DEFAULT NULL, ADD tel_syndic VARCHAR(128) DEFAULT NULL, ADD tel_syndic_secondaire VARCHAR(128) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP denomination_syndic, DROP nom_syndic, DROP mail_syndic, DROP tel_syndic, DROP tel_syndic_secondaire');
    }
}
