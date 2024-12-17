<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241217130151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace "diagnostique" by "diagnostic" in desordre_precision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE desordre_precision SET label = REPLACE(label, 'Diagnostique', 'Diagnostic') WHERE label like '%diagnostique%'");
    }

    public function down(Schema $schema): void
    {
    }
}
