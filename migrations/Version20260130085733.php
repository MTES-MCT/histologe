<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260130085733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rename procedures from DIOGENE to SALETE';
    }

    public function up(Schema $schema): void
    {
        // In partner table, in column competence, replace TEXT 'DIOGENE' by 'SALETE' in all rows
        $this->addSql("UPDATE partner SET competence = REPLACE(competence, 'DIOGENE', 'SALETE') WHERE competence LIKE '%DIOGENE%'");

        // In table desordre_precision, in column qualification, for desordre_precision_slug starting with 'desordres_logement_proprete', add 'SALETE' in json list
        $this->addSql("UPDATE desordre_precision SET qualification = JSON_ARRAY_APPEND(qualification, '$', 'SALETE') WHERE desordre_precision_slug LIKE 'desordres_logement_proprete%'");

        // No signalement_qualification with DIOGENE to update
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE partner SET competence = REPLACE(competence, 'SALETE', 'DIOGENE') WHERE competence LIKE '%SALETE%'");

        $this->addSql("UPDATE desordre_precision SET qualification = JSON_REMOVE(qualification, JSON_UNQUOTE(JSON_SEARCH(qualification, 'one', 'SALETE'))) WHERE desordre_precision_slug LIKE 'desordres_logement_proprete%'");
    }
}
