<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414120442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne is_mailing_club_event à la table user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_mailing_club_event TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_mailing_club_event');
    }
}
