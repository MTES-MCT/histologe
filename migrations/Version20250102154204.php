<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Dto\Request\Signalement\QualificationNDERequest;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250102154204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rollback fake signalement date_entree if another real date has been entered';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `signalement`
                        SET `date_entree` = JSON_EXTRACT(type_composition_logement, \'$.bail_dpe_date_emmenagement\')
                        WHERE
                            (`date_entree` = :dateEntreeBefore2023 OR `date_entree` = :dateEntreeAfter2023)
                            AND JSON_EXTRACT(type_composition_logement, \'$.bail_dpe_date_emmenagement\') IS NOT NULL', [
            'dateEntreeBefore2023' => QualificationNDERequest::RADIO_VALUE_BEFORE_2023,
            'dateEntreeAfter2023' => QualificationNDERequest::RADIO_VALUE_AFTER_2023,
        ]);
    }

    public function down(Schema $schema): void
    {
    }
}
