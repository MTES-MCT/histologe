<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323155252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add territory_id to service_secours_route';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_secours_route ADD territory_id INT DEFAULT NULL AFTER id');
        $this->addSql('ALTER TABLE service_secours_route ADD CONSTRAINT FK_3121EF1173F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('CREATE INDEX IDX_3121EF1173F74AD4 ON service_secours_route (territory_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_secours_route DROP FOREIGN KEY FK_3121EF1173F74AD4');
        $this->addSql('DROP INDEX IDX_3121EF1173F74AD4 ON service_secours_route');
        $this->addSql('ALTER TABLE service_secours_route DROP territory_id');
    }
}
