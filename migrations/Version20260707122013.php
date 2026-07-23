<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707122013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes travaux_mise_en_conformite et precisions_cloture à la table affectation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation ADD travaux_mise_en_conformite VARCHAR(255) DEFAULT NULL, ADD precisions_cloture LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation DROP travaux_mise_en_conformite, DROP precisions_cloture');
    }
}
