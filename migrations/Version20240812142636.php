<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240812142636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add territory timezone';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory ADD timezone VARCHAR(128) NOT NULL');

        $this->addSql("UPDATE territory SET timezone = 'America/Guadeloupe' WHERE zip LIKE '971'");
        $this->addSql("UPDATE territory SET timezone = 'America/Martinique' WHERE zip LIKE '972'");
        $this->addSql("UPDATE territory SET timezone = 'America/Cayenne' WHERE zip LIKE '973'");
        $this->addSql("UPDATE territory SET timezone = 'Indian/Reunion' WHERE zip LIKE '974'");
        $this->addSql("UPDATE territory SET timezone = 'Indian/Mayotte' WHERE zip LIKE '976'");

        $this->addSql("UPDATE territory SET timezone = 'Europe/Paris' WHERE zip NOT LIKE '97%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory DROP timezone');
    }
}
