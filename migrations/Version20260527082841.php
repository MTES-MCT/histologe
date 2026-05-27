<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527082841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set isLogementVacant to false on signalements not from service secours form, where isLogementVacant is null and occupant name is known';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE signalement
             SET is_logement_vacant = false
             WHERE is_logement_vacant IS NULL
               AND creation_source <> 'FORM_SERVICE_SECOURS'
               AND nom_occupant IS NOT NULL
               AND nom_occupant != ''"
        );
    }

    public function down(Schema $schema): void
    {
    }
}
