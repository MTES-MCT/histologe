<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230530094337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add proprietaire_present to intervention table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention ADD proprietaire_present TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention DROP proprietaire_present');
    }
}
