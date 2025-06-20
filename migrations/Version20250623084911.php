<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250623084911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create suivi_file table and is_standalone field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE suivi_file (id INT AUTO_INCREMENT NOT NULL, suivi_id INT NOT NULL, file_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_3CBF61727FEA59C0 (suivi_id), INDEX IDX_3CBF617293CB796C (file_id), UNIQUE INDEX unique_suivi_file (suivi_id, file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi_file ADD CONSTRAINT FK_3CBF61727FEA59C0 FOREIGN KEY (suivi_id) REFERENCES suivi (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi_file ADD CONSTRAINT FK_3CBF617293CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file ADD is_standalone TINYINT(1) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi_file DROP FOREIGN KEY FK_3CBF61727FEA59C0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi_file DROP FOREIGN KEY FK_3CBF617293CB796C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE suivi_file
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file DROP is_standalone
        SQL);
    }
}
