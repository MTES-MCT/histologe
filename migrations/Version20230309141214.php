<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230309141214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop sci and syndic columns from signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP mail_syndic, DROP nom_sci, DROP nom_representant_sci, DROP tel_sci, DROP mail_sci, DROP tel_syndic, DROP nom_syndic');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD mail_syndic VARCHAR(255) DEFAULT NULL, ADD nom_sci VARCHAR(255) DEFAULT NULL, ADD nom_representant_sci VARCHAR(255) DEFAULT NULL, ADD tel_sci VARCHAR(12) DEFAULT NULL, ADD mail_sci VARCHAR(255) DEFAULT NULL, ADD tel_syndic VARCHAR(12) DEFAULT NULL, ADD nom_syndic VARCHAR(255) DEFAULT NULL');
    }
}
