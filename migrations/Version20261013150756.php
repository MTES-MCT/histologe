<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\DepartementRegionHelper;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013150756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add region table and link territory.region_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE region (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        foreach (DepartementRegionHelper::getRegions() as $regionName) {
            $this->addSql('INSERT INTO region (name) VALUES (?)', [$regionName]);
        }

        $this->addSql('ALTER TABLE territory ADD region_id INT DEFAULT NULL');

        foreach (DepartementRegionHelper::DEPARTEMENT_REGION as $zip => $regionName) {
            $this->addSql(
                'UPDATE territory SET region_id = (SELECT id FROM region WHERE name = ?) WHERE zip = ?',
                [$regionName, (string) $zip]
            );
        }

        $this->addSql('ALTER TABLE territory CHANGE region_id region_id INT NOT NULL');
        $this->addSql('ALTER TABLE territory ADD CONSTRAINT FK_E974396698260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('CREATE INDEX IDX_E974396698260155 ON territory (region_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory DROP FOREIGN KEY FK_E974396698260155');
        $this->addSql('DROP INDEX IDX_E974396698260155 ON territory');
        $this->addSql('ALTER TABLE territory DROP region_id');
        $this->addSql('DROP TABLE region');
    }
}
