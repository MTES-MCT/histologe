<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250221154939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
                UPDATE signalement_draft d
                SET status = 'EN_SIGNALEMENT'
                WHERE EXISTS (
                    SELECT 1
                    FROM signalement s
                    WHERE s.created_from_id = d.id
                    AND d.status != 'EN_SIGNALEMENT'
                )
            ");
    }

    public function down(Schema $schema): void
    {
    }
}
