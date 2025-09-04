<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250902133331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_api_permission table and add partner_id on file and suivi tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_api_permission (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, territory_id INT DEFAULT NULL, partner_id INT DEFAULT NULL, partner_type VARCHAR(255) DEFAULT NULL COMMENT \'Value possible enum PartnerType\', INDEX IDX_4C6631E2A76ED395 (user_id), INDEX IDX_4C6631E273F74AD4 (territory_id), INDEX IDX_4C6631E29393F8FE (partner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_api_permission ADD CONSTRAINT FK_4C6631E2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_api_permission ADD CONSTRAINT FK_4C6631E273F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('ALTER TABLE user_api_permission ADD CONSTRAINT FK_4C6631E29393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)');
        $this->addSql('ALTER TABLE file ADD partner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36109393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)');
        $this->addSql('CREATE INDEX IDX_8C9F36109393F8FE ON file (partner_id)');
        $this->addSql('ALTER TABLE suivi ADD partner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE suivi ADD CONSTRAINT FK_2EBCCA8F9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)');
        $this->addSql('CREATE INDEX IDX_2EBCCA8F9393F8FE ON suivi (partner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_api_permission DROP FOREIGN KEY FK_4C6631E2A76ED395');
        $this->addSql('ALTER TABLE user_api_permission DROP FOREIGN KEY FK_4C6631E273F74AD4');
        $this->addSql('ALTER TABLE user_api_permission DROP FOREIGN KEY FK_4C6631E29393F8FE');
        $this->addSql('DROP TABLE user_api_permission');
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36109393F8FE');
        $this->addSql('DROP INDEX IDX_8C9F36109393F8FE ON file');
        $this->addSql('ALTER TABLE file DROP partner_id');
        $this->addSql('ALTER TABLE suivi DROP FOREIGN KEY FK_2EBCCA8F9393F8FE');
        $this->addSql('DROP INDEX IDX_2EBCCA8F9393F8FE ON suivi');
        $this->addSql('ALTER TABLE suivi DROP partner_id');
    }
}
