<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409095143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Desynchronise les affectations Oilhi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
                UPDATE affectation a
                INNER JOIN partner p
                    ON p.id = a.partner_id
                SET a.is_synchronized = 0
                WHERE a.is_synchronized = 1
                    AND p.esabora_url IS NULL
                    AND (
                        JSON_CONTAINS(p.insee, '"62091"')
                        OR JSON_CONTAINS(p.insee, '"55502"')
                        OR JSON_CONTAINS(p.insee, '"55029"')
                        OR JSON_CONTAINS(p.insee, '"55545"')
                    )
                SQL
        );
    }

    public function down(Schema $schema): void
    {
    }
}
