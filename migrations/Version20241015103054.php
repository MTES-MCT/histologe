<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241015103054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create zone and partner_zone tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE partner_zone (partner_id INT NOT NULL, zone_id INT NOT NULL, INDEX IDX_F0DFF31F9393F8FE (partner_id), INDEX IDX_F0DFF31F9F2C3FAB (zone_id), PRIMARY KEY(partner_id, zone_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, territory_id INT NOT NULL, created_by_id INT NOT NULL, area LONGTEXT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_A0EBC00773F74AD4 (territory_id), INDEX IDX_A0EBC007B03A8386 (created_by_id), UNIQUE INDEX UNIQ_A0EBC0075E237E0673F74AD4 (name, territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE partner_zone ADD CONSTRAINT FK_F0DFF31F9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partner_zone ADD CONSTRAINT FK_F0DFF31F9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_A0EBC00773F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_A0EBC007B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner_zone DROP FOREIGN KEY FK_F0DFF31F9393F8FE');
        $this->addSql('ALTER TABLE partner_zone DROP FOREIGN KEY FK_F0DFF31F9F2C3FAB');
        $this->addSql('ALTER TABLE zone DROP FOREIGN KEY FK_A0EBC00773F74AD4');
        $this->addSql('ALTER TABLE zone DROP FOREIGN KEY FK_A0EBC007B03A8386');
        $this->addSql('DROP TABLE partner_zone');
        $this->addSql('DROP TABLE zone');
    }
}
