<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250618103218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create duplicate_addresse_detection table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE duplicate_addresse_detection (id INT AUTO_INCREMENT NOT NULL, territory_id INT NOT NULL, address VARCHAR(255) NOT NULL, zip VARCHAR(5) NOT NULL, city VARCHAR(100) NOT NULL, nb_duplication INT NOT NULL, INDEX IDX_B1E87E1773F74AD4 (territory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE duplicate_addresse_detection ADD CONSTRAINT FK_B1E87E1773F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE duplicate_addresse_detection DROP FOREIGN KEY FK_B1E87E1773F74AD4
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE duplicate_addresse_detection
        SQL);
    }
}
