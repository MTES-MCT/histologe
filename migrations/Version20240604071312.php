<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240604071312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add location and zone tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, lat DOUBLE PRECISION NOT NULL, lng DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, territory_id INT NOT NULL, wkt LONGTEXT NOT NULL, INDEX IDX_A0EBC00773F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_A0EBC00773F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP FOREIGN KEY FK_A0EBC00773F74AD4');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE zone');
    }
}
