<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230929101332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete documents and photos column in signalement and interventions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention DROP documents');
        $this->addSql('ALTER TABLE signalement DROP photos, DROP documents');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention ADD documents JSON NOT NULL');
        $this->addSql('ALTER TABLE signalement ADD photos JSON DEFAULT NULL, ADD documents JSON DEFAULT NULL');
    }
}
