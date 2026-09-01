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

    private const REF_WITH_MOTIF_ABANDON = [
        '2023-9', '2023-17', '2023-33', '2023-38', '2023-61', '2023-68', '2023-87', '2023-104', '2023-111', '2023-139', '2023-146', '2023-147', '2023-158', '2023-185', '2023-188', '2024-2', '2024-16', '2024-27', '2024-28', '2024-29', '2024-39', '2024-40', '2024-41', '2024-53', '2024-63', '2024-68', '2024-69', '2024-76', '2024-79', '2024-81', '2024-91', '2024-100', '2024-101', '2024-106', '2024-111', '2024-118', '2024-146', '2024-147', '2024-150', '2024-152', '2024-153', '2024-178', '2024-187', '2024-190', '2024-203', '2024-207', '2024-212', '2024-218', '2024-219', '2024-228', '2024-231', '2024-256', '2024-270', '2024-278', '2024-281', '2024-283', '2024-292', '2024-293', '2024-295', '2024-297', '2024-341', '2024-342', '2024-346', '2024-359', '2024-363', '2024-374', '2024-378', '2024-388', '2024-390', '2024-400', '2024-402', '2024-405', '2024-427', '2024-429', '2024-433', '2024-434', '2024-439', '2024-453', '2024-463', '2024-478', '2024-494', '2024-495', '2024-510', '2024-516', '2024-529', '2024-530', '2024-542', '2024-549', '2024-563', '2024-573', '2024-586', '2024-595', '2024-626', '2024-638', '2024-649', '2024-650', '2024-655', '2024-676', '2024-678', '2024-693', '2024-701', '2024-705', '2024-707', '2024-708', '2024-725', '2024-741', '2024-782', '2024-792', '2024-844', '2024-851', '2024-860', '2024-861', '2024-863', '2024-911', '2024-914', '2024-935', '2024-937', '2024-938', '2024-941', '2024-946', '2024-949', '2024-962', '2024-967', '2024-970', '2024-973', '2024-979', '2024-984', '2024-991', '2024-993', '2024-994', '2024-995', '2024-1013', '2024-1020', '2024-1023', '2024-1034', '2024-1037', '2024-1038', '2024-1041', '2024-1049', '2024-1053', '2024-1062', '2024-1066', '2024-1074', '2024-1090', '2024-1096', '2024-1098', '2024-1100', '2024-1103', '2024-1108', '2024-1109', '2024-1111', '2024-1119', '2024-1120', '2024-1138', '2024-1142', '2024-1147', '2024-1151', '2024-1167', '2024-1169', '2024-1171', '2024-1172', '2024-1179', '2024-1184', '2024-1192', '2024-1193', '2024-1204', '2024-1212', '2024-1217', '2024-1218', '2024-1222', '2024-1230', '2024-1248', '2024-1250', '2024-1253', '2024-1255', '2024-1258', '2024-1259', '2024-1262', '2024-1270', '2024-1275', '2025-8', '2025-11', '2025-18', '2025-31', '2025-34', '2025-35', '2025-36', '2025-37', '2025-54', '2025-55', '2025-56', '2025-57', '2025-65', '2025-74', '2025-76', '2025-82', '2025-90', '2025-98', '2025-101', '2025-104', '2025-110', '2025-117', '2025-119', '2025-121', '2025-127', '2025-130', '2025-132', '2025-139', '2025-141', '2025-144', '2025-159', '2025-162', '2025-164', '2025-165', '2025-166', '2025-169', '2025-170', '2025-173', '2025-183', '2025-191', '2025-203', '2025-204', '2025-205', '2025-206', '2025-209', '2025-216', '2025-220', '2025-225', '2025-231', '2025-250', '2025-256', '2025-260', '2025-262', '2025-273', '2025-276', '2025-280', '2025-283', '2025-289', '2025-299', '2025-301', '2025-305', '2025-306', '2025-316', '2025-328', '2025-331', '2025-335', '2025-337', '2025-339', '2025-340', '2025-341', '2025-342', '2025-349', '2025-358', '2025-362', '2025-366', '2025-368', '2025-374', '2025-378', '2025-379', '2025-386', '2025-388', '2025-389', '2025-397', '2025-402', '2025-403', '2025-405', '2025-415', '2025-419', '2025-420', '2025-424', '2025-443', '2025-456', '2025-467', '2025-472', '2025-476', '2025-478', '2025-487', '2025-491', '2025-509', '2025-513', '2025-529', '2025-551', '2025-552', '2025-553', '2025-565', '2025-572', '2025-576', '2025-617', '2025-638', '2025-642', '2025-644',
    ];

    private const MESSAGE_WITH_MOTIF_ABANDON = 'Absence de mise à jour de votre dossier depuis plus d\'un an. En cas de désaccord, vous pouvez demander la réouverture du dossier pendant 30 jours, directement depuis votre espace.';

    private const REF_WITH_MOTIF_DEPART_OCCUPANT = [];

    private const REF_WITH_MOTIF_DOUBLON = [];

    private const REF_WITH_MOTIF_LOGEMENT_VENDU = [];

    private const REF_WITH_MOTIF_REFUS_TRAVAUX = [];

    private const REF_WITH_MOTIF_REFUS_VISITE = [];

    private const REF_WITH_MOTIF_RELOGEMENT = [];

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

        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_ABANDON, MotifCloture::ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE, self::MESSAGE_WITH_MOTIF_ABANDON);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_DEPART_OCCUPANT, MotifCloture::DEPART_OCCUPANT);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_DOUBLON, MotifCloture::DOUBLON);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_LOGEMENT_VENDU, MotifCloture::LOGEMENT_VENDU);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_REFUS_TRAVAUX, MotifCloture::REFUS_DE_TRAVAUX);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_REFUS_VISITE, MotifCloture::REFUS_DE_VISITE);
        $this->forceCloturesDossiers($io, self::REF_WITH_MOTIF_RELOGEMENT, MotifCloture::RELOGEMENT_OCCUPANT);

        return Command::SUCCESS;
    }

    /** @param array<string> $refs */
    private function forceCloturesDossiers(SymfonyStyle $io, array $refs, MotifCloture $motif, ?string $message = null): void
    {
        // Gestion volontaire dossier par dossier pour avoir le bilan à la fin à transmettre
        foreach ($refs as $ref) {
            $signalement = $this->signalementRepository->findOneByWithAddressCriteria(['s.reference' => $ref, 'address.territory' => $this->territory]);
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
                ->setComCloture($message ?? $motif->label())
                ->setClosedBy($this->adminUser);

            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: SuiviManager::buildDescriptionClotureSignalement(
                    [
                        'subject' => 'tous les partenaires',
                        'motif_cloture' => $motif,
                        'motif_suivi' => $message ?? $motif->label(),
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
