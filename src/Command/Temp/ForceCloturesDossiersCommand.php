<?php

namespace App\Command\Temp;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use League\Flysystem\FilesystemException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:force-clotures-dossiers',
    description: 'Commande de fermeture de dossiers dans le 78, avec une liste de signalements et de motifs.'
)]
class ForceCloturesDossiersCommand extends Command
{
    private const TERRITORY = '78';

    private const CLOTURE_MESSAGE = 'Clôture forcée par Signal Logement.';

    private const REF_WITH_MOTIF_ABANDON = [
        '2023-2', '2023-4', '2023-12', '2023-14', '2023-23', '2023-28', '2023-59',
        '2023-62', '2023-63', '2023-66', '2023-67', '2023-69', '2023-89', '2023-94', '2023-96', '2023-98', '2023-99',
        '2023-107', '2023-110', '2023-113', '2023-119', '2023-122', '2023-125', '2023-128', '2023-137', '2023-142', '2023-152',
        '2023-157', '2023-162', '2023-165', '2023-170', '2023-173', '2023-174', '2023-184', '2024-3', '2024-9', '2024-11',
        '2024-12', '2024-13', '2024-15', '2024-24', '2024-25', '2024-26', '2024-32', '2024-33', '2024-34', '2024-35', '2024-38',
        '2024-42', '2024-45', '2024-46', '2024-57', '2024-59', '2024-62', '2024-65', '2024-67', '2024-70', '2024-71', '2024-73',
        '2024-74', '2024-80', '2024-83', '2024-84', '2024-87', '2024-96', '2024-99', '2024-110', '2024-113', '2024-115', '2024-117',
        '2024-121', '2024-123', '2024-125', '2024-127', '2024-130', '2024-135', '2024-139', '2024-140', '2024-141', '2024-148',
        '2024-157', '2024-163', '2024-164', '2024-165', '2024-167', '2024-170', '2024-172', '2024-173', '2024-174', '2024-177',
        '2024-182', '2024-183', '2024-188', '2024-189', '2024-194', '2024-195', '2024-196', '2024-201', '2024-202', '2024-204',
        '2024-208', '2024-215', '2024-220', '2024-223', '2024-226', '2024-232', '2024-233', '2024-242', '2024-243', '2024-247',
        '2024-251', '2024-254', '2024-257', '2024-258', '2024-259', '2024-263', '2024-268', '2024-275', '2024-276', '2024-280',
        '2024-286', '2024-287', '2024-289', '2024-298', '2024-300', '2024-302', '2024-312', '2024-320', '2024-321', '2024-330',
        '2024-339', '2024-343', '2024-344', '2024-351', '2024-352', '2024-365', '2024-369', '2024-372', '2024-380', '2024-382',
        '2024-394', '2024-395', '2024-399', '2024-409', '2024-413', '2024-414', '2024-415', '2024-423', '2024-425', '2024-428',
        '2024-430', '2024-431', '2024-436', '2024-437', '2024-442', '2024-458', '2024-464', '2024-470', '2024-472', '2024-477',
        '2024-479', '2024-481', '2024-484', '2024-489', '2024-502', '2024-504', '2024-507', '2024-509', '2024-512', '2024-517',
        '2024-518', '2024-519', '2024-522', '2024-526', '2024-527', '2024-535', '2024-538', '2024-539', '2024-541', '2024-547',
        '2024-548', '2024-558', '2024-566', '2024-571', '2024-576', '2024-587', '2024-591', '2024-593', '2024-599', '2024-604',
        '2024-609', '2024-611', '2024-613', '2024-630', '2024-631', '2024-632', '2024-633', '2024-635', '2024-642', '2024-654',
        '2024-662', '2024-665', '2024-672', '2024-674', '2024-675', '2024-698', '2024-699', '2024-703', '2024-710', '2024-732',
        '2024-735', '2024-739', '2024-742', '2024-746', '2024-749', '2024-751', '2024-752', '2024-755', '2024-756', '2024-758',
        '2024-759', '2024-768', '2024-771', '2024-772', '2024-775', '2024-791', '2024-793', '2024-794', '2024-796', '2024-803',
        '2024-815', '2024-816', '2024-817', '2024-819', '2024-820', '2024-824', '2024-831', '2024-843', '2024-847', '2024-857',
        '2024-858', '2024-884', '2024-902', '2024-928', '2024-929', '2024-945', '2024-947', '2024-952', '2024-960', '2024-980',
        '2024-1005', '2024-1006', '2024-1018', '2024-1075', '2024-1105', '2024-1149', '2024-1162', '2024-1186', '2024-1189',
        '2024-1229', '2024-1245', '2025-4', '2025-61', '2025-67', '2025-78', '2025-85', '2025-99', '2025-113', '2025-116', '2025-293',
        '2026-97', '2026-45', '2026-254', '2026-127', '2026-114', '2025-994', '2025-903', '2025-839', '2025-820', '2025-781',
        '2025-758', '2025-750', '2025-732', '2025-665', '2025-658', '2025-636', '2025-574', '2025-570', '2025-564', '2025-541',
        '2025-493', '2025-410', '2025-392', '2025-390', '2025-348', '2025-327', '2025-326', '2025-311', '2025-284', '2025-26',
        '2025-232', '2025-20', '2025-196', '2025-195', '2025-190', '2025-179', '2025-167', '2025-1090', '2025-107', '2025-1046',
        '2024-922', '2024-848', '2024-796', '2024-764', '2024-734', '2024-570', '2024-432', '2024-411', '2024-349', '2024-236',
        '2024-222', '2024-154', '2024-1261', '2024-1252', '2024-1237', '2024-1199', '2024-1191', '2024-1173', '2024-1162',
        '2024-1155', '2024-1153', '2024-1067', '2024-1009', '2023-21', '2023-180',
    ];

    private const REF_WITH_MOTIF_DEPART_OCCUPANT = [
        '2025-594', '2025-591', '2025-546', '2025-528', '2025-518', '2025-498', '2025-494', '2025-484', '2025-479', '2025-453',
        '2025-447', '2025-414', '2025-40', '2025-373', '2025-370', '2025-360', '2025-356', '2025-290', '2025-281', '2025-28',
        '2025-266', '2025-255', '2025-241', '2025-237', '2025-235', '2025-230', '2025-228', '2025-218', '2025-212', '2025-211',
        '2025-188', '2025-186', '2025-182', '2025-172', '2025-153', '2025-146', '2025-142', '2025-12', '2025-1085', '2025-1073',
        '2025-1071', '2025-1034', '2024-996', '2024-992', '2024-972', '2024-915', '2024-871', '2024-862', '2024-817', '2024-815',
        '2024-677', '2024-670', '2024-668', '2024-508', '2024-500', '2024-497', '2024-43', '2024-366', '2024-335', '2024-1278',
        '2024-1245', '2024-1241', '2024-1240', '2024-1228', '2024-1205', '2024-1195', '2024-1185', '2024-1160', '2024-1157',
        '2024-1145', '2024-1136', '2024-1129', '2024-1126', '2024-1124', '2024-1121', '2024-1095', '2024-1083', '2024-1073',
        '2024-1071', '2024-1039', '2024-1018', '2024-1001', '2023-97', '2023-39', '2023-189', '2023-143',
    ];

    private const REF_WITH_MOTIF_DOUBLON = [
        '2025-819', '2025-806', '2025-516', '2025-451', '2024-943', '2024-733', '2024-1087',
    ];

    private const REF_WITH_MOTIF_LOGEMENT_VENDU = [
        '2025-826',
    ];

    private const REF_WITH_MOTIF_REFUS_TRAVAUX = [
        '2025-99', '2025-827', '2025-785', '2025-449', '2025-1026', '2024-685', '2024-215', '2024-1015',
    ];

    private const REF_WITH_MOTIF_REFUS_VISITE = [
        '2025-756', '2025-660', '2025-654', '2025-581', '2025-577', '2025-545', '2024-1188',
    ];

    private const REF_WITH_MOTIF_RELOGEMENT = [
        '2025-88', '2025-49', '2025-442', '2025-431', '2025-383', '2025-347', '2025-332', '2025-30', '2025-257', '2025-252',
        '2025-25', '2025-199', '2025-1054', '2024-989', '2024-977', '2024-868', '2024-505', '2024-271', '2024-1280',
        '2024-1144', '2024-1114', '2023-177',
    ];

    private Territory $territory;
    private User $adminUser;
    private Partner $adminUserPartner;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly SignalementRepository $signalementRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AffectationManager $affectationManager,
        private readonly SuiviManager $suiviManager,
    ) {
        parent::__construct();
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     * @throws FilesystemException
     * @throws QueryException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->territory = $this->territoryRepository->findOneBy(['zip' => self::TERRITORY]);
        $this->adminUser = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
        $this->adminUserPartner = $this->adminUser->getPartnerInTerritoryOrFirstOne($this->territory);

        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_ABANDON, MotifCloture::ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_DEPART_OCCUPANT, MotifCloture::DEPART_OCCUPANT);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_DOUBLON, MotifCloture::DOUBLON);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_LOGEMENT_VENDU, MotifCloture::LOGEMENT_VENDU);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_REFUS_TRAVAUX, MotifCloture::REFUS_DE_TRAVAUX);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_REFUS_VISITE, MotifCloture::REFUS_DE_VISITE);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_RELOGEMENT, MotifCloture::RELOGEMENT_OCCUPANT);

        return Command::SUCCESS;
    }

    /** @param array<string> $refs */
    private function forceCloturesDossiers(SymfonyStyle $io, array $refs, MotifCloture $motif): void
    {
        // Gestion volontaire dossier par dossier pour avoir le bilan à la fin à transmettre
        foreach ($refs as $ref) {
            $signalement = $this->signalementRepository->findOneBy(['reference' => $ref, 'territory' => $this->territory]);
            if (!$signalement) {
                $io->error("Aucun signalement dans le territoire {$this->territory->getZip()} avec la référence {$ref}.");
                continue;
            }

            // Pas utile de le faire si le signalement n'est pas actif
            if (SignalementStatus::ACTIVE !== $signalement->getStatut()) {
                $io->warning("Le signalement avec la référence {$ref} n'est pas actif.");
                continue;
            }

            // Pas utile de le faire si il y a des suivis partenaires qui ont moins d'un an
            if ($this->suiviManager->hasSuiviPartnerNewerThan($signalement, new \DateTimeImmutable('-1 year'))) {
                $io->warning("Le signalement avec la référence {$ref} a des suivis partenaires de moins d'un an.");
                continue;
            }

            $signalement
                ->setStatut(SignalementStatus::CLOSED)
                ->setMotifCloture($motif)
                ->setClosedAt(new \DateTimeImmutable())
                ->setComCloture(self::CLOTURE_MESSAGE)
                ->setClosedBy($this->adminUser);

            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: SuiviManager::buildDescriptionClotureSignalement(
                    [
                        'subject' => 'tous les partenaires',
                        'motif_cloture' => $motif,
                        'motif_suivi' => self::CLOTURE_MESSAGE,
                    ]
                ),
                category: SuiviCategory::SIGNALEMENT_IS_CLOSED,
                partner: $this->adminUserPartner,
                user: $this->adminUser,
                isVisibleForUsager: true,
            );
            $signalement->addSuivi($suivi);

            $this->affectationManager->closeBySignalement($signalement, $motif, $this->adminUser, $this->adminUserPartner);

            $io->success("Le signalement avec la référence {$ref} a été fermé avec le motif {$motif->label()}.");
        }

        $this->entityManager->flush();
    }
}
