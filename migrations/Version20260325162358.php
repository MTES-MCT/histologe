<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325162358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add many-to-many relation between Partner and Epci';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE partner_epci (partner_id INT NOT NULL, epci_id INT NOT NULL, INDEX IDX_53C762FD9393F8FE (partner_id), INDEX IDX_53C762FD4E7C18CB (epci_id), PRIMARY KEY(partner_id, epci_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE partner_epci ADD CONSTRAINT FK_53C762FD9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partner_epci ADD CONSTRAINT FK_53C762FD4E7C18CB FOREIGN KEY (epci_id) REFERENCES epci (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner_epci DROP FOREIGN KEY FK_53C762FD9393F8FE');
        $this->addSql('ALTER TABLE partner_epci DROP FOREIGN KEY FK_53C762FD4E7C18CB');
        $this->addSql('DROP TABLE partner_epci');
    }
}
