<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250408132851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove territory from admin partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE partner SET territory_id = NULL WHERE id = 1');
    }

    public function down(Schema $schema): void
    {
    }
}
