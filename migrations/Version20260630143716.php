<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630143716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Note and TagUser entities with their relationships and constraints.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, signalement_id INT DEFAULT NULL, content LONGTEXT NOT NULL, INDEX IDX_CFBDFA14A76ED395 (user_id), INDEX IDX_CFBDFA1465C5E57E (signalement_id), UNIQUE INDEX unique_user_signalement (user_id, signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag_user (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_639C69FFA76ED395 (user_id), UNIQUE INDEX unique_label_user (label, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag_user_signalement (tag_user_id INT NOT NULL, signalement_id INT NOT NULL, INDEX IDX_9F1EF34955FFE3F (tag_user_id), INDEX IDX_9F1EF3465C5E57E (signalement_id), PRIMARY KEY(tag_user_id, signalement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA1465C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('ALTER TABLE tag_user ADD CONSTRAINT FK_639C69FFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tag_user_signalement ADD CONSTRAINT FK_9F1EF34955FFE3F FOREIGN KEY (tag_user_id) REFERENCES tag_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag_user_signalement ADD CONSTRAINT FK_9F1EF3465C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14A76ED395');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA1465C5E57E');
        $this->addSql('ALTER TABLE tag_user DROP FOREIGN KEY FK_639C69FFA76ED395');
        $this->addSql('ALTER TABLE tag_user_signalement DROP FOREIGN KEY FK_9F1EF34955FFE3F');
        $this->addSql('ALTER TABLE tag_user_signalement DROP FOREIGN KEY FK_9F1EF3465C5E57E');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE tag_user');
        $this->addSql('DROP TABLE tag_user_signalement');
    }
}
