<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250402123310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove partner_id and territory_id from user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64973F74AD4');
        if ('histologe' !== getenv('APP')) {
            $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499393F8FE');
        } else {
            $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64998DE13AC');
        }
        $this->addSql('DROP INDEX IDX_8D93D64973F74AD4 ON user');
        $this->addSql('DROP INDEX IDX_8D93D6499393F8FE ON user');
        $this->addSql('ALTER TABLE user DROP partner_id, DROP territory_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD partner_id INT DEFAULT NULL, ADD territory_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64973F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8D93D64973F74AD4 ON user (territory_id)');
        $this->addSql('CREATE INDEX IDX_8D93D6499393F8FE ON user (partner_id)');
    }
}
