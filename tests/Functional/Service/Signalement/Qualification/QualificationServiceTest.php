<?php

namespace App\Tests\Functional\Service\Signalement\Qualification;

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
        array $qualificationsStatusToCheck,
        string $isAssuranceContactee = 'non'
    ): void {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-27']);
        $signalement->setDateEntree(new \DateTimeImmutable()); // for NDE
        // remove all DesordrePrecision
        foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
            $signalement->removeDesordrePrecision($desordrePrecision);
        }
        // set new DesordrePrecisions
        $signalement->setScore($score);

        $signalement->getInformationProcedure()->setInfoProcedureAssuranceContactee($isAssuranceContactee);

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
            'desordres_type_composition_logement_cuisine_collective_oui',
            'desordres_logement_humidite_piece_a_vivre_details_fuite_oui',
        ];
        yield 'Score 1, NON_DECENCE et ASSURANTIEL car assurance non contactée' => [
            1,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::ASSURANTIEL],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::ASSURANTIEL_CHECK],
            'non',
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_cuisine_collective_oui',
            'desordres_logement_humidite_piece_a_vivre_details_fuite_oui',
        ];
        yield 'Score 1, NON_DECENCE et ASSURANTIEL car ne sait pas' => [
            1,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::ASSURANTIEL],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::ASSURANTIEL_CHECK],
            'nsp',
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_cuisine_collective_oui',
            'desordres_logement_humidite_piece_a_vivre_details_fuite_oui',
        ];
        yield 'Score 1, NON_DECENCE et pas ASSURANTIEL car assurance contactée' => [
            1,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE],
            [QualificationStatus::NON_DECENCE_CHECK],
            'oui',
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_cuisine_collective_oui',
        ];
        yield 'Score entre 0 et 10, NON DECENCE' => [
            2,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE],
            [QualificationStatus::NON_DECENCE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9',
        ];
        yield 'Score entre 0 et 10, NON DECENCE et RSD' => [
            3,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::RSD],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::RSD_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_accessibilite_acces_batiment',
        ];
        yield 'Score entre 0 et 10, RSD' => [
            6,
            $listSlugDesordrePrecision,
            [Qualification::RSD],
            [QualificationStatus::RSD_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_batiment_accessibilite_acces_batiment',
        ];
        yield 'Score entre 0 et 10, RSD et INSALUBRITE OBLIGATOIRE' => [
            7,
            $listSlugDesordrePrecision,
            [Qualification::RSD, Qualification::INSALUBRITE],
            [QualificationStatus::RSD_CHECK, QualificationStatus::INSALUBRITE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_cuisine_collective_oui',
        ];
        yield 'Score entre 0 et 10, NON DECENCE et INSALUBRITE OBLIGATOIRE' => [
            9,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::INSALUBRITE],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::INSALUBRITE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9',
        ];
        yield 'Score entre 0 et 10, NON DECENCE et RSD et INSALUBRITE OBLIGATOIRE' => [
            5,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::RSD, Qualification::INSALUBRITE],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::INSALUBRITE_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_cuisine_collective_oui',
        ];
        yield 'Score entre 10 et 30, NON DECENCE et MANQUEMENT A LA SALUBRITE' => [
            12,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::INSALUBRITE],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9',
        ];
        yield 'Score entre 10 et 30, NON DECENCE et RSD et MANQUEMENT A LA SALUBRITE' => [
            23,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::RSD, Qualification::INSALUBRITE],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_accessibilite_acces_batiment',
        ];
        yield 'Score entre 10 et 30, RSD et MANQUEMENT A LA SALUBRITE' => [
            26,
            $listSlugDesordrePrecision,
            [Qualification::RSD, Qualification::INSALUBRITE],
            [QualificationStatus::RSD_CHECK, QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_batiment_accessibilite_acces_batiment',
        ];
        yield 'Score entre 10 et 30, RSD et INSALUBRITE OBLIGATOIRE' => [
            17,
            $listSlugDesordrePrecision,
            [Qualification::RSD, Qualification::INSALUBRITE],
            [QualificationStatus::RSD_CHECK, QualificationStatus::INSALUBRITE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_cuisine_collective_oui',
        ];
        yield 'Score entre 10 et 30, NON DECENCE et INSALUBRITE OBLIGATOIRE' => [
            29,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::INSALUBRITE],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::INSALUBRITE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9',
        ];
        yield 'Score entre 10 et 30, NON DECENCE et RSD et INSALUBRITE OBLIGATOIRE' => [
            25,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::RSD, Qualification::INSALUBRITE],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::INSALUBRITE_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_batiment_accessibilite_acces_batiment',
        ];
        yield 'Score entre 30 et 50, RSD et INSALUBRITE' => [
            37,
            $listSlugDesordrePrecision,
            [Qualification::RSD, Qualification::INSALUBRITE],
            [QualificationStatus::RSD_CHECK, QualificationStatus::INSALUBRITE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_cuisine_collective_oui',
        ];
        yield 'Score entre 30 et 50, NON DECENCE et INSALUBRITE' => [
            49,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::INSALUBRITE],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::INSALUBRITE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9',
        ];
        yield 'Score entre 30 et 50, NON DECENCE et RSD et INSALUBRITE' => [
            35,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::RSD, Qualification::INSALUBRITE],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::INSALUBRITE_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_batiment_accessibilite_acces_batiment',
        ];
        yield 'Score supérieur à 50, RSD et INSALUBRITE' => [
            57,
            $listSlugDesordrePrecision,
            [Qualification::RSD, Qualification::INSALUBRITE],
            [QualificationStatus::RSD_CHECK, QualificationStatus::INSALUBRITE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_cuisine_collective_oui',
        ];
        yield 'Score supérieur à 50, NON DECENCE et INSALUBRITE' => [
            69,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::INSALUBRITE],
            [QualificationStatus::NON_DECENCE_CHECK, QualificationStatus::INSALUBRITE_CHECK],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9',
        ];
        yield 'Score supérieur à 50, NON DECENCE et RSD et INSALUBRITE' => [
            75,
            $listSlugDesordrePrecision,
            [Qualification::NON_DECENCE, Qualification::RSD, Qualification::INSALUBRITE],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::INSALUBRITE_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_type_composition_logement_sous_combles',
            'desordres_type_composition_logement_plusieurs_pieces_aucune_piece_9',
            'desordres_batiment_maintenance_ascenseur',
        ];
        yield 'Score supérieur à 50, NON DECENCE et RSD et INSALUBRITE et PERIL' => [
            75,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::MISE_EN_SECURITE_PERIL,
                Qualification::INSALUBRITE,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::MISE_EN_SECURITE_PERIL_CHECK,
                QualificationStatus::INSALUBRITE_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_securite_sol_details_plancher_effondre',
        ];
        yield 'score 10 NON_DECENCE, RSD et DANGER' => [
            10,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::DANGER,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::DANGER_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_securite_sol_details_plancher_effondre',
        ];
        yield 'score 80 NON_DECENCE, RSD, MISE_EN_SECURITE_PERIL, INSALUBRITE et DANGER' => [
            80,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::MISE_EN_SECURITE_PERIL,
                Qualification::INSALUBRITE,
                Qualification::DANGER,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::MISE_EN_SECURITE_PERIL_CHECK,
                QualificationStatus::INSALUBRITE_CHECK,
                QualificationStatus::DANGER_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_isolation_dernier_etage_toit_sous_combles',
            'desordres_type_composition_logement_suroccupation_non_allocataire',
        ];
        yield 'score 10 NON_DECENCE, RSD et is_suroccupation' => [
            10,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::SUROCCUPATION,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::SUROCCUPATION_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_isolation_dernier_etage_toit_sous_combles',
            'desordres_type_composition_logement_suroccupation_non_allocataire',
        ];
        yield 'score 40 NON_DECENCE, RSD, INSALUBRITE et is_suroccupation' => [
            40,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE,
                Qualification::RSD,
                Qualification::INSALUBRITE,
                Qualification::SUROCCUPATION,
            ],
            [
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
                QualificationStatus::INSALUBRITE_CHECK,
                QualificationStatus::SUROCCUPATION_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_batiment_isolation_dernier_etage_toit_sous_combles',
            'desordres_type_composition_logement_suroccupation_non_allocataire',
        ];
        yield 'score 60 NON_DECENCE, RSD, MISE_EN_SECURITE_PERIL, INSALUBRITE et is_suroccupation' => [
            60,
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
                QualificationStatus::INSALUBRITE_CHECK,
                QualificationStatus::SUROCCUPATION_CHECK,
            ],
        ];

        $listSlugDesordrePrecision = [
            'desordres_logement_chauffage_details_chauffage_KO_pieces_salle_de_bain',
        ];
        yield 'NON_DECENCE, RSD, NON_DECENCE_ENERGETIQUE' => [
            1,
            $listSlugDesordrePrecision,
            [
                Qualification::NON_DECENCE_ENERGETIQUE,
                Qualification::NON_DECENCE,
                Qualification::RSD,
            ],
            [
                QualificationStatus::NDE_CHECK,
                QualificationStatus::NON_DECENCE_CHECK,
                QualificationStatus::RSD_CHECK,
            ],
        ];
    }
}
