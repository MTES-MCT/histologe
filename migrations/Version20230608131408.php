<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230608131408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new field to intervention table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention ADD additional_information JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention DROP additional_information');
    }
}
