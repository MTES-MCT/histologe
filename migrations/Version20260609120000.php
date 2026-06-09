<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260609120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove email_delivery_issue records caused by disabled Brevo templates (not a recipient issue)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM email_delivery_issue WHERE reason = 'template is disabled'");
    }

    public function down(Schema $schema): void
    {
        $this->warnIf(true, 'Cannot restore deleted email_delivery_issue records.');
    }
}
