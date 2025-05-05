<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250505105401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout d\'une contrainte unique sur user_id et partner_id dans la table user_partner.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE partner RENAME INDEX fk_312b3e1657b5d0a2 TO IDX_312B3E1657B5D0A2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user RENAME INDEX uniq_8d93d64961f0bb97 TO UNIQ_8D93D64921CADBFB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX unique_user_partner ON user_partner (user_id, partner_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE partner RENAME INDEX idx_312b3e1657b5d0a2 TO FK_312B3E1657B5D0A2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user RENAME INDEX uniq_8d93d64921cadbfb TO UNIQ_8D93D64961F0BB97
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX unique_user_partner ON user_partner
        SQL);
    }
}
