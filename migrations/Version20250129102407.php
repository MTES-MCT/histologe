<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250129102407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add on delete cascade on notification suivi_id and delete suivis for arretes and visites created on 2025-01-29';
    }

    public function up(Schema $schema): void
    {
        // add on delete cascade on notification suivi_id
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA7FEA59C0');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA7FEA59C0 FOREIGN KEY (suivi_id) REFERENCES suivi (id) ON DELETE CASCADE');
        // delete suivis for arretes and visites created on 2025-01-29
        $this->addSql("DELETE FROM suivi WHERE description LIKE '%pris dans le dossier de%' AND context = 'intervention' AND created_at LIKE '2025-01-29%'");
        $this->addSql("DELETE FROM suivi WHERE description LIKE 'Visite réalisée : une visite du logement situé%' AND context = 'intervention' AND created_at LIKE '2025-01-29%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA7FEA59C0');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA7FEA59C0 FOREIGN KEY (suivi_id) REFERENCES suivi (id)');
    }
}
