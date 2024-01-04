<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Criticite;
use App\Entity\DesordrePrecision;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QualificationServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    protected ManagerRegistry $managerRegistry;
    private SignalementQualificationUpdater $signalementQualificationUpdater;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $container = static::getContainer();
        $this->signalementQualificationUpdater = $container->get(SignalementQualificationUpdater::class);
    }

    /**
     * @dataProvider provideScoreAndCriticite
     */
    public function testInitQualification(int $score, array $listCriticites, array $qualificationsToCheck): void
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $criticiteRepository = $this->entityManager->getRepository(Criticite::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);
        $signalement->setScore($score);
        foreach ($listCriticites as $criticite) {
            $signalement->addCriticite($criticiteRepository->findOneBy(['label' => $criticite]));
        }

        $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);
        $signalementQualifications = $signalement->getSignalementQualifications();

        $this->assertEquals(\count($qualificationsToCheck), \count($signalementQualifications));
    }

    private function provideScoreAndCriticite(): \Generator
    {
        yield 'RSD, NON_DECENCE' => [5, [], [Qualification::RSD, Qualification::NON_DECENCE]];

        yield 'RSD, NON_DECENCE, INSALUBRITE by score' => [
            11,
            [],
            [Qualification::RSD, Qualification::NON_DECENCE, Qualification::INSALUBRITE],
        ];

        $listLabelCriticites = [
            'Pièce unique du logement de moins de 9m2',
        ];
        yield 'RSD, NON_DECENCE, INSALUBRITE by criticite' => [
            5,
            $listLabelCriticites,
            [Qualification::RSD, Qualification::NON_DECENCE, Qualification::INSALUBRITE],
        ];

        $listLabelCriticites = [
            'Hauteur sous plafond du logement de moins de 2.20m',
        ];
        yield 'RSD, NON_DECENCE, DANGER' => [
            5,
            $listLabelCriticites,
            [Qualification::RSD, Qualification::NON_DECENCE, Qualification::DANGER],
        ];

        $listLabelCriticites = [
            'Hauteur sous plafond du logement de moins de 2.20m',
        ];
        yield 'RSD, NON_DECENCE, DANGER, INSALUBRITE by score' => [
            11,
            $listLabelCriticites,
            [Qualification::RSD, Qualification::NON_DECENCE, Qualification::DANGER, Qualification::INSALUBRITE],
        ];

        $listLabelCriticites = [
            'Hauteur sous plafond du logement de moins de 2.20m',
            'Pièce unique du logement de moins de 9m2',
        ];
        yield 'RSD, NON_DECENCE, DANGER, INSALUBRITE by criticite' => [
            5,
            $listLabelCriticites,
            [Qualification::RSD, Qualification::NON_DECENCE, Qualification::DANGER, Qualification::INSALUBRITE],
        ];
    }

    /**
     * @dataProvider provideScoreAndDesordresPrecisions
     */
    public function testInitQualificationNewSignalements(
        int $score,
        array $listDesordrePrecision,
        array $qualificationsToCheck,
        array $qualificationsStatusToCheck
    ): void {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-27']);
        // remove all DesordrePrecision
        foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
            $signalement->removeDesordrePrecision($desordrePrecision);
        }
        // set new DesordrePrecisions
        $signalement->setScore($score);
        foreach ($listDesordrePrecision as $desordrePrecisionSlug) {
            $signalement->addDesordrePrecision($desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $desordrePrecisionSlug]
            ));
        }

        $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);
        $signalementQualifications = $signalement->getSignalementQualifications();

        $i = 0;
        foreach ($signalementQualifications as $signalementQualification) {
            $this->assertEquals($qualificationsToCheck[$i], $signalementQualification->getQualification());
            $this->assertEquals($qualificationsStatusToCheck[$i], $signalementQualification->getStatus());
            ++$i;
        }
        $this->assertEquals(\count($qualificationsToCheck), \count($signalementQualifications));
    }

    private function provideScoreAndDesordresPrecisions(): \Generator
    {
        yield 'nothing' => [
            5,
            [],
            [],
            [],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_proprete_interieur',
        ];
        yield 'NON_DECENCE, RSD' => [
            1,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::RSD],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::RSD_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_eau_evacuation_KO',
        ];
        yield 'NON_DECENCE, RSD, INSALUBRITE score 10' => [
            10,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::INSALUBRITE,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK,
            ],
        ];
        yield 'NON_DECENCE, RSD, INSALUBRITE score 40' => [
            40,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::INSALUBRITE,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::INSALUBRITE_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_isolation_dernier_etage_toit_sous_toit_oui',
        ];
        yield 'NON_DECENCE, RSD, MISE_EN_SECURITE_PERIL' => [
            10,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::MISE_EN_SECURITE_PERIL,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::MISE_EN_SECURITE_PERIL_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_securite_sol_details_plancher_effondre',
        ];
        yield 'NON_DECENCE, RSD, MISE_EN_SECURITE_PERIL et is_danger' => [
            10,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::MISE_EN_SECURITE_PERIL,
                Qualification::DANGER,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::MISE_EN_SECURITE_PERIL_CHECK,
                QualificationStatus::DANGER_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_isolation_dernier_etage_toit_sous_toit_oui',
            'desordres_type_composition_logement_suroccupation_non_allocataire',
        ];
        yield 'NON_DECENCE, RSD, MISE_EN_SECURITE_PERIL, INSALUBRITE et is_suroccupation' => [
            10,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::MISE_EN_SECURITE_PERIL,
                Qualification::INSALUBRITE,
                Qualification::SUROCCUPATION,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::MISE_EN_SECURITE_PERIL_CHECK,
                QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK,
                QualificationStatus::SUROCCUPATION_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_logement_humidite_cuisine_details_fuite_oui',
        ];
        yield 'ASSURANTIEL' => [
            1,
            $listSlugDesordrePrecision,
            [Qualification::ASSURANTIEL],
            [QualificationStatus::ASSURANTIEL_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_isolation_dernier_etage_toit_sous_toit_oui',
            'desordres_logement_humidite_cuisine_details_moisissure_apres_nettoyage_non',
            'desordres_logement_humidite_cuisine_details_machine_oui',
            'desordres_logement_humidite_piece_a_vivre_details_machine_non',
            'desordres_logement_chauffage_details_difficultes_chauffage_pieces_piece_a_vivre',
            'desordres_batiment_proprete_local_poubelles',
            'desordres_batiment_isolation_infiltration_eau_au_sol_non',
            'desordres_batiment_maintenance_petites_reparations',
            'desordres_batiment_securite_escalier_details_utilisable',
            'desordres_batiment_accessibilite_acces_batiment',
        ];
        yield 'LOT OF DESORDRESPRECISIONS' => [
            10,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::MISE_EN_SECURITE_PERIL,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::MISE_EN_SECURITE_PERIL_CHECK,
            ],
        ];
    }
}
