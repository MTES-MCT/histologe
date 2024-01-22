<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240115090308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add desordre_precisions_ids in signalement_qualification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_qualification ADD desordre_precision_ids JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_qualification DROP desordre_precision_ids');
    }
}
