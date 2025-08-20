<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250818150757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add commentBeforeVisite field to Intervention entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention ADD comment_before_visite TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention DROP comment_before_visite');
    }
}
