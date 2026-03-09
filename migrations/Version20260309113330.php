<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\AppContext;
use App\Entity\Enum\DesordreCritereZone;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309113330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add app_context column to desordre_categorie table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_categorie ADD app_context VARCHAR(32) DEFAULT NULL');
        $this->addSql(
            'UPDATE desordre_categorie SET app_context = :defaultContext WHERE app_context IS NULL',
            ['defaultContext' => AppContext::DEFAULT->value]
        );

        $this->addSql(
            'INSERT INTO desordre_categorie (label, created_at, app_context)
                     VALUES (:label, NOW(), :serviceSecoursContext)',
            [
                'label' => 'Service Secours',
                'serviceSecoursContext' => AppContext::SERVICE_SECOURS->value,
            ]
        );

        $this->addSql(
            'INSERT INTO desordre_categorie (label, created_at, app_context)
                     SELECT :label, NOW(), :serviceSecoursContext
                     WHERE NOT EXISTS (
                         SELECT 1
                         FROM desordre_categorie
                         WHERE label = :label
                     )',
            [
                'label' => 'Service Secours',
                'serviceSecoursContext' => AppContext::SERVICE_SECOURS->value,
            ]
        );

        $criteres = [
            [
                'slugCritere' => 'desordres_service_secours_logement_inadapte',
                'labelCritere' => 'Logement inadapté',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_humidite_moisissures',
                'labelCritere' => 'Humidité généralisée, moisissures',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_chauffage_dangereux',
                'labelCritere' => 'Chauffage dangereux',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_risque_electrique',
                'labelCritere' => 'Risque électrique',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_salete_accumulation_dechets',
                'labelCritere' => 'Saleté, accumulation de déchets',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_mauvais_etat_batiment',
                'labelCritere' => 'Mauvais état du bâti',
                'zoneCategorie' => DesordreCritereZone::BATIMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_absence_confort',
                'labelCritere' => 'Absence de confort (chauffage, eau, etc.)',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_securite_personnes',
                'labelCritere' => 'Sécurité des personnes',
                'zoneCategorie' => DesordreCritereZone::BATIMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_risque_saturnisme',
                'labelCritere' => 'Risque de saturnisme',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_nuisibles',
                'labelCritere' => 'Nuisibles',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_parties_communes_degradees',
                'labelCritere' => 'Parties communes dégradées',
                'zoneCategorie' => DesordreCritereZone::BATIMENT->value,
            ],
            [
                'slugCritere' => 'desordres_service_secours_autre',
                'labelCritere' => 'Autre',
                'zoneCategorie' => DesordreCritereZone::LOGEMENT->value,
            ],
        ];

        foreach ($criteres as $critere) {
            $this->addSql(
                'INSERT INTO desordre_critere (
                            slug_categorie,
                            label_categorie,
                            zone_categorie,
                            slug_critere,
                            label_critere,
                            desordre_categorie_id,
                            config_precision_libre_type,
                            config_precision_libre_label,
                            created_at,
                            updated_at
                        )
                        SELECT
                            :slugCategorie,
                            :labelCategorie,
                            :zoneCategorie,
                            :slugCritere,
                            :labelCritere,
                            dc.id,
                            NULL,
                            NULL,
                            NOW(),
                            NULL
                        FROM desordre_categorie dc
                        WHERE dc.label = :labelCategorie
                          AND dc.app_context = :serviceSecoursContext
                          AND NOT EXISTS (
                              SELECT 1
                              FROM desordre_critere d
                              WHERE d.slug_critere = :slugCritere
                          )',
                [
                    'slugCategorie' => 'service_secours',
                    'labelCategorie' => 'Service Secours',
                    'zoneCategorie' => $critere['zoneCategorie'],
                    'slugCritere' => $critere['slugCritere'],
                    'labelCritere' => $critere['labelCritere'],
                    'serviceSecoursContext' => AppContext::SERVICE_SECOURS->value,
                ]
            );
        }

        $precisions = [
            [
                'slugCritere' => 'desordres_service_secours_logement_inadapte',
                'slugPrecision' => 'desordres_service_secours_logement_inadapte_logement_exigu',
                'label' => 'Logement très exigu, bas de plafond (inférieur à 2,20m), sans ouvrant vers l\'extérieur avec lumière naturelle insuffisante, etc.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_humidite_moisissures',
                'slugPrecision' => 'desordres_service_secours_humidite_moisissures_fuites',
                'label' => 'Fuites ou infiltrations d’eau, tâches de moisissures, forte odeur d’humidité, etc.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_chauffage_dangereux',
                'slugPrecision' => 'desordres_service_secours_chauffage_dangereux_logement_calfeutre',
                'label' => 'Logement calfeutré, sans ventilation, appareil ou conduit en mauvais état, toute situation menant à un risque d’intoxication au monoxyde de carbone.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_risque_electrique',
                'slugPrecision' => 'desordres_service_secours_risque_electrique_absence_compteur',
                'label' => 'Absence de compteur individuel, risques de contact direct avec des fils dénudés, traces d’échauffement, etc.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_salete_accumulation_dechets',
                'slugPrecision' => 'desordres_service_secours_salete_accumulation_dechets_logement_sale',
                'label' => 'Logement très sale, encombré de déchets, isolement social, refus d’accès au logement.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_mauvais_etat_batiment',
                'slugPrecision' => 'desordres_service_secours_mauvais_etat_batiment_structure',
                'label' => 'Présence de fissures, plancher anormalement instable, risque de chute d’éléments (cheminée, tuiles, plafonds, escalier désolidarisé), etc.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_absence_confort',
                'slugPrecision' => 'desordres_service_secours_absence_confort_salubrite',
                'label' => 'Chauffage absent ou insuffisant, ventilation non fonctionnelle, ouvrants en mauvais état, absence d’eau chaude, de cuisine avec évier, de WC, de salle d’eau, remontées d’odeur de canalisations, etc.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_securite_personnes',
                'slugPrecision' => 'desordres_service_secours_securite_personnes_risque_chute',
                'label' => 'Escaliers dangereux, garde-corps (fenêtres, escaliers) instable ou absent, sol avec ressauts, etc.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_risque_saturnisme',
                'slugPrecision' => 'desordres_service_secours_risque_saturnisme_personne_vulnerable',
                'label' => 'Peintures dégradées, présence d’enfant ou de femme enceinte dans un immeuble ancien',
            ],
            [
                'slugCritere' => 'desordres_service_secours_nuisibles',
                'slugPrecision' => 'desordres_service_secours_nuisibles_infestations',
                'label' => 'Rats, souris, punaises de lit, cafards, etc.',
            ],
            [
                'slugCritere' => 'desordres_service_secours_parties_communes_degradees',
                'slugPrecision' => 'desordres_service_secours_parties_communes_degradees_precision',
                'label' => null,
            ],
            [
                'slugCritere' => 'desordres_service_secours_autre',
                'slugPrecision' => 'desordres_service_secours_autre_precision',
                'label' => null,
            ],
        ];

        foreach ($precisions as $precision) {
            $this->addSql(
                'INSERT INTO desordre_precision (
                    coef,
                    is_danger,
                    is_suroccupation,
                    is_insalubrite,
                    label,
                    qualification,
                    desordre_critere_id,
                    desordre_precision_slug,
                    config_is_unique,
                    created_at,
                    updated_at
                )
                SELECT
                    :coef,
                    :is_danger,
                    :is_suroccupation,
                    NULL,
                    :label,
                    :qualification,
                    dc.id,
                    :slugPrecision,
                    :configIsUnique,
                    NOW(),
                    NOW()
                FROM desordre_critere dc
                INNER JOIN desordre_categorie cat ON cat.id = dc.desordre_categorie_id
                WHERE dc.slug_critere = :slugCritere
                  AND cat.app_context = :serviceSecoursContext
                  AND NOT EXISTS (
                      SELECT 1
                      FROM desordre_precision dp
                      WHERE dp.desordre_precision_slug = :slugPrecision
                  )',
                [
                    'coef' => 0,
                    'is_danger' => 0,
                    'is_suroccupation' => 0,
                    'label' => $precision['label'],
                    'qualification' => '[]',
                    'slugPrecision' => $precision['slugPrecision'],
                    'configIsUnique' => 0,
                    'slugCritere' => $precision['slugCritere'],
                    'serviceSecoursContext' => AppContext::SERVICE_SECOURS->value,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM desordre_precision
        WHERE desordre_precision_slug LIKE :slugPrefix',
            [
                'slugPrefix' => 'desordres_service_secours_%',
            ]
        );

        $this->addSql(
            'DELETE FROM desordre_critere
        WHERE slug_critere LIKE :slugPrefix',
            [
                'slugPrefix' => 'desordres_service_secours_%',
            ]
        );

        $this->addSql(
            'DELETE FROM desordre_categorie
        WHERE app_context = :appContext',
            [
                'appContext' => AppContext::SERVICE_SECOURS->value,
            ]
        );

        $this->addSql('ALTER TABLE desordre_categorie DROP app_context');
    }
}
