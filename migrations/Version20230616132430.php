<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230616132430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, signalement_id INT NOT NULL, filename VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, file_type VARCHAR(32) NOT NULL, INDEX IDX_8C9F3610A76ED395 (user_id), INDEX IDX_8C9F361065C5E57E (signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE file CHANGE file_type file_type VARCHAR(32) NOT NULL COMMENT \'Value possible photo or document\'');
        $this->addSql('ALTER TABLE file ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F361065C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F3610A76ED395');
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F361065C5E57E');
        $this->addSql('DROP TABLE file');
    }
}
