<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630143716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add PersonalNote and PersonalTag entities with their relationships and constraints.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE personal_note (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, signalement_id INT DEFAULT NULL, content LONGTEXT NOT NULL, INDEX IDX_FEB7FD29A76ED395 (user_id), INDEX IDX_FEB7FD2965C5E57E (signalement_id), UNIQUE INDEX unique_user_signalement (user_id, signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personal_tag (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_6700652DA76ED395 (user_id), UNIQUE INDEX unique_label_user (label, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personal_tag_signalement (personal_tag_id INT NOT NULL, signalement_id INT NOT NULL, INDEX IDX_3517EFEB4E5E8F06 (personal_tag_id), INDEX IDX_3517EFEB65C5E57E (signalement_id), PRIMARY KEY(personal_tag_id, signalement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE personal_note ADD CONSTRAINT FK_FEB7FD29A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE personal_note ADD CONSTRAINT FK_FEB7FD2965C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('ALTER TABLE personal_tag ADD CONSTRAINT FK_6700652DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE personal_tag_signalement ADD CONSTRAINT FK_3517EFEB4E5E8F06 FOREIGN KEY (personal_tag_id) REFERENCES personal_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_tag_signalement ADD CONSTRAINT FK_3517EFEB65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personal_note DROP FOREIGN KEY FK_FEB7FD29A76ED395');
        $this->addSql('ALTER TABLE personal_note DROP FOREIGN KEY FK_FEB7FD2965C5E57E');
        $this->addSql('ALTER TABLE personal_tag DROP FOREIGN KEY FK_6700652DA76ED395');
        $this->addSql('ALTER TABLE personal_tag_signalement DROP FOREIGN KEY FK_3517EFEB4E5E8F06');
        $this->addSql('ALTER TABLE personal_tag_signalement DROP FOREIGN KEY FK_3517EFEB65C5E57E');
        $this->addSql('DROP TABLE personal_note');
        $this->addSql('DROP TABLE personal_tag');
        $this->addSql('DROP TABLE personal_tag_signalement');
    }
}
