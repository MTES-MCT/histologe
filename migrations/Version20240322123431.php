<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240322123431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a tag to email or achived user or partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE partner SET email = CONCAT(email, '.archived@', DATE_FORMAT(NOW(), '%Y%m%d%H%i')) WHERE is_archive = 1"
        );
        $this->addSql(
            "UPDATE user SET email = CONCAT(email, '.archived@', DATE_FORMAT(NOW(), '%Y%m%d%H%i')) WHERE statut = 2"
        );
    }

    public function down(Schema $schema): void
    {
    }
}
