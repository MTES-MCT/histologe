<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401092031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add qualifications to desordre precisions';
    }

    private const array QUALIFICATIONS_FOR_DESORDRE_PRECISIONS = [
        'desordres_service_secours_logement_inadapte_logement_exigu' => ['RSD', 'NON_DECENCE', 'INSALUBRITE'],
        'desordres_service_secours_humidite_moisissures_fuites' => ['RSD', 'NON_DECENCE', 'INSALUBRITE'],
        'desordres_service_secours_chauffage_dangereux_logement_calfeutre' => ['is_danger'],
        'desordres_service_secours_risque_electrique_absence_compteur' => ['is_danger'],
        'desordres_service_secours_salete_accumulation_dechets_logement_sale' => ['SALETE'],
        'desordres_service_secours_risque_saturnisme_personne_vulnerable' => ['is_danger', 'INSALUBRITE'],
        'desordres_service_secours_nuisibles_infestations' => ['NON_DECENCE'],
    ];

    public function up(Schema $schema): void
    {
        foreach (self::QUALIFICATIONS_FOR_DESORDRE_PRECISIONS as $desordrePrecisionSlug => $qualifications) {
            if (in_array('is_danger', $qualifications)) {
                $this->addSql('UPDATE desordre_precision SET is_danger = 1 WHERE desordre_precision_slug = :slug', [
                    'slug' => $desordrePrecisionSlug,
                ]);
                // remove first element of array to avoid storing 'is_danger' in qualification field
                array_shift($qualifications);
            }
            if (in_array('INSALUBRITE', $qualifications)) {
                $this->addSql('UPDATE desordre_precision SET is_insalubrite = 1 WHERE desordre_precision_slug = :slug', [
                    'slug' => $desordrePrecisionSlug,
                ]);
            }
            $this->addSql('UPDATE desordre_precision SET qualification = :qualification WHERE desordre_precision_slug = :slug', [
                'qualification' => json_encode($qualifications),
                'slug' => $desordrePrecisionSlug,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::QUALIFICATIONS_FOR_DESORDRE_PRECISIONS as $desordrePrecisionSlug => $qualifications) {
            $this->addSql('UPDATE desordre_precision SET qualification = :qualification WHERE desordre_precision_slug = :slug', [
                'qualification' => json_encode([]),
                'is_danger' => 0,
                'is_insalubrite' => 0,
                'slug' => $desordrePrecisionSlug,
            ]);
        }
    }
}
