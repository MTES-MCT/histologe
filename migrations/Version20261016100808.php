<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261016100808 extends AbstractMigration
{
    private const string DESCRIPTION_VACANT = 'Le logement a été marqué comme vacant.';
    private const string DESCRIPTION_OCCUPE = 'Le logement a été marqué comme occupé.';

    public function getDescription(): string
    {
        return 'Replace description text by logementVacant boolean in pending BO_EDIT_OCCUPATION_LOGEMENT suivi_delayed';
    }

    public function up(Schema $schema): void
    {
        foreach ([self::DESCRIPTION_VACANT => 'true', self::DESCRIPTION_OCCUPE => 'false'] as $description => $logementVacant) {
            $this->addSql(
                "UPDATE suivi_delayed SET changes = JSON_OBJECT('logementVacant', CAST('{$logementVacant}' AS JSON))
                WHERE suivi_delayed_type = 'BO_EDIT_OCCUPATION_LOGEMENT'
                AND JSON_UNQUOTE(JSON_EXTRACT(changes, '$.description')) = :description",
                ['description' => $description]
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach ([self::DESCRIPTION_VACANT => 'true', self::DESCRIPTION_OCCUPE => 'false'] as $description => $logementVacant) {
            $this->addSql(
                "UPDATE suivi_delayed SET changes = JSON_OBJECT('description', :description)
                WHERE suivi_delayed_type = 'BO_EDIT_OCCUPATION_LOGEMENT'
                AND JSON_EXTRACT(changes, '$.logementVacant') = CAST('{$logementVacant}' AS JSON)",
                ['description' => $description]
            );
        }
    }
}
