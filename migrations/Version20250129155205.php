<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250129155205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create pop_notification table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE pop_notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', params JSON NOT NULL, INDEX IDX_257F9F96A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pop_notification ADD CONSTRAINT FK_257F9F96A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pop_notification DROP FOREIGN KEY FK_257F9F96A76ED395');
        $this->addSql('DROP TABLE pop_notification');
    }
}
