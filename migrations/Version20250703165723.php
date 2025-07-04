<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250703165723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'set is_esabora_active=0 if is_esabora_active is 1 but esabora_url or esabora_token is NULL.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE partner SET is_esabora_active = 0 WHERE is_esabora_active = 1 AND (esabora_url IS NULL OR esabora_token IS NULL)');
    }

    public function down(Schema $schema): void
    {
        // Non réversible
    }
}
