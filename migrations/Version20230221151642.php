<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230221151642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update commune, criticite and partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune ADD is_zone_permis_louer TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE criticite ADD qualification JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE partner ADD type VARCHAR(255) DEFAULT NULL, ADD competence JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune DROP is_zone_permis_louer');
        $this->addSql('ALTER TABLE criticite DROP qualification');
        $this->addSql('ALTER TABLE partner DROP type, DROP competence');
    }
}
