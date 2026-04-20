<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410082704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ datePriseDeVue dans l\'entité File';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD date_prise_de_vue DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP date_prise_de_vue');
    }
}
