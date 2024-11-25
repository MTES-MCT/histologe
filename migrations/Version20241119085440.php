<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241119085440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE partner_excluded_zone (partner_id INT NOT NULL, zone_id INT NOT NULL, INDEX IDX_EF69B0979393F8FE (partner_id), INDEX IDX_EF69B0979F2C3FAB (zone_id), PRIMARY KEY(partner_id, zone_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE partner_excluded_zone ADD CONSTRAINT FK_EF69B0979393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partner_excluded_zone ADD CONSTRAINT FK_EF69B0979F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner_excluded_zone DROP FOREIGN KEY FK_EF69B0979393F8FE');
        $this->addSql('ALTER TABLE partner_excluded_zone DROP FOREIGN KEY FK_EF69B0979F2C3FAB');
        $this->addSql('DROP TABLE partner_excluded_zone');
    }
}
