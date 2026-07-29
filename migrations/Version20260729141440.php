<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260729141440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change les suivis existants en catégorue INTERVENTION_HAS_CONCLUSION et INTERVENTION_HAS_CONCLUSION_EDITED de type AUTO à PARTNER';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            "UPDATE suivi SET type = %d WHERE category IN ('%s', '%s') AND type = %d",
            Suivi::TYPE_PARTNER,
            SuiviCategory::INTERVENTION_HAS_CONCLUSION->value,
            SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED->value,
            Suivi::TYPE_AUTO
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf(
            "UPDATE suivi SET type = %d WHERE category IN ('%s', '%s') AND type = %d",
            Suivi::TYPE_AUTO,
            SuiviCategory::INTERVENTION_HAS_CONCLUSION->value,
            SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED->value,
            Suivi::TYPE_PARTNER
        ));
    }
}
