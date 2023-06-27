<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230612133653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add is_post_visite field to signalement_qualification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_qualification ADD is_post_visite TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_qualification DROP is_post_visite');
    }
}
