<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250102154204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rollback fake signalement date_entree if another real date has been entered';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `signalement` SET `date_entree` = \'2012-03-06\' WHERE id = 49266');
        $this->addSql('UPDATE `signalement` SET `date_entree` = \'2024-08-12\' WHERE id = 68601');
    }

    public function down(Schema $schema): void
    {
    }
}
