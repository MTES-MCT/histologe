<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260127065244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create service_secours_route table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE service_secours_route (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_3121EF11D17F50A6 (uuid), UNIQUE INDEX UNIQ_3121EF115E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE service_secours_route');
    }
}
