<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231205105240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_suroccupation to desordre_precision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_precision ADD is_suroccupation TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_precision DROP is_suroccupation');
    }
}
