<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221017125117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE affectation LEFT JOIN user ON affectation.territory_id = user.territory_id SET affectation.partner_id=user.partner_id WHERE affectation.partner_id = 1 AND user.roles = \'["ROLE_ADMIN_TERRITORY"]\'');
    }

    public function down(Schema $schema): void
    {
    }
}
