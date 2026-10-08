<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\InterconnectionAuthType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008110823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set authentication_type to STATIC_TOKEN for partners with esabora_token IS NOT NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            "UPDATE partner SET authentication_type = '%s' WHERE esabora_token IS NOT NULL",
            InterconnectionAuthType::STATIC_TOKEN->value
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE partner SET authentication_type = NULL WHERE esabora_token IS NOT NULL');
    }
}
