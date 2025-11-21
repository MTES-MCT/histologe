<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251114134013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table user_saved_search';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_saved_search (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(50) NOT NULL, params JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_503ADCBEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_saved_search ADD CONSTRAINT FK_503ADCBEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_saved_search DROP FOREIGN KEY FK_503ADCBEA76ED395');
        $this->addSql('DROP TABLE user_saved_search');
    }
}
