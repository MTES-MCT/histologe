<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241021102820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create API user token table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_user_token (id INT AUTO_INCREMENT NOT NULL, owned_by_id INT NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_7A5F26725F37A13B (token), UNIQUE INDEX UNIQ_7A5F26725E70BCD7 (owned_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_user_token ADD CONSTRAINT FK_7A5F26725E70BCD7 FOREIGN KEY (owned_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_user_token DROP FOREIGN KEY FK_7A5F26725E70BCD7');
        $this->addSql('DROP TABLE api_user_token');
    }
}
