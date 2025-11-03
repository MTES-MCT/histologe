<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251103143954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace les codes status 209 par 400 dans la table job_event pour le service idoss.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE job_event SET code_status = 400 WHERE service = 'idoss' AND code_status = 209");
        $this->addSql('DROP TABLE IF EXISTS job');
    }

    public function down(Schema $schema): void
    {
    }
}
