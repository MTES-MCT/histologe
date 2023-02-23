<?php

namespace App\Command;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Territory;
use App\Repository\CommuneRepository;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:update-partners-communes-nde',
    description: 'Update Partners and communes of 2 territories for non décence énergétique',
)]
class UpdatePartnerAndCommuneNDECommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private ParameterBagInterface $parameterBag,
        private CommuneRepository $communeRepository,
        private PartnerRepository $partnerRepository,
        private TerritoryRepository $territoryRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->section('<info>Update communes in zone permis de louer in Puy de dôme</info>');
        $communesInseePermisDeLouer63 = [63430, 63102, 63125];
        $this->updateCommunes($communesInseePermisDeLouer63);
        $this->io->section('<info>Update communes in zone permis de louer in Yonne</info>');
        $communesInseePermisDeLouer89 = [89206, 89348, 89382, 89418];
        $this->updateCommunes($communesInseePermisDeLouer89);

        $this->io->section('<info>Update partners in Puy de dôme</info>');
        $territory63 = $this->territoryRepository->findOneBy(['zip' => '63']);
        $this->updatePartner($territory63, 'ADIL', PartnerType::ADIL, true, null);
        $this->updatePartner($territory63, 'ALF (OPAH)', PartnerType::Dispositif_renovation_habitat, false, null);
        $this->updatePartner($territory63, 'MSA', PartnerType::CAF_MSA, true, null);
        $this->updatePartner($territory63, 'API', PartnerType::EPCI, false, ['63178', '63005', '63006', '63007', '63009', '63017', '63160', '63022', '63029', '63031', '63036', '63046', '63050', '63051', '63052', '63054', '63073', '63074', '63079', '63080', '63087', '63088', '63091', '63097', '63109', '63111', '63114', '63121', '63122', '63134', '63145', '63156', '63166', '63172', '63182', '63185', '63448', '63199', '63202', '63209', '63220', '63222', '63234', '63241', '63242', '63250', '63255', '63261', '63268', '63269', '63270', '63275', '63277', '63282', '63287', '63299', '63303', '63313', '63321', '63330', '63340', '63342', '63348', '63352', '63356', '63357', '63366', '63367', '63376', '63375', '63389', '63392', '63403', '63404', '63409', '63411', '63415', '63422', '63423', '63429', '63435', '63439', '63442', '63444', '63452', '63456', '63458', '63466']);
        $this->updatePartner($territory63, 'ARS', PartnerType::ARS, false, null);
        $this->updatePartner($territory63, 'Soliha', PartnerType::Operateur_visites_et_travaux, false, null);
        $this->updatePartner($territory63, 'BILLOM-CO', PartnerType::EPCI, false, ['63040', '63034', '63044', '63049', '63096', '63106', '63146', '63154', '63155', '63157', '63168', '63177', '63216', '63239', '63226', '63252', '63273', '63297', '63325', '63334', '63365', '63368', '63438', '63445', '63453']);
        $this->updatePartner($territory63, 'CAF', PartnerType::CAF_MSA, true, null);
        $this->updatePartner($territory63, 'DDTM', PartnerType::DDT_M, false, null);
        $this->updatePartner($territory63, 'Métropole de Clermont', PartnerType::EPCI, false, ['63113', '63014', '63019', '63032', '63042', '63063', '63069', '63070', '63075', '63099', '63124', '63141', '63164', '63193', '63254', '63263', '63272', '63284', '63307', '63308', '63345']);
        $this->updatePartner($territory63, 'PAYS DE SAINT-ELOY', PartnerType::EPCI, false, ['63338', '63011', '63025', '63041', '63060', '63062', '63067', '63094', '63101', '63130', '63140', '63152', '63171', '63187', '63223', '63233', '63243', '63251', '63281', '63293', '63304', '63354', '63360', '63369', '63373', '63377', '63388', '63329', '63408', '63419', '63428', '63447', '63462', '63471']);
        $this->updatePartner($territory63, 'PIG CD', PartnerType::Dispositif_renovation_habitat, false, null);
        $this->updatePartner($territory63, 'RLV', PartnerType::EPCI, false, ['63300', '63244', '63083', '63089', '63092', '63103', '63107', '63108', '63112', '63148', '63149', '63150', '63200', '63203', '63204', '63212', '63213', '63215', '63224', '63245', '63278', '63290', '63322', '63327', '63362', '63372', '63381', '63417', '63424', '63443', '63470']);
        $this->updatePartner($territory63, 'SCHS CF', PartnerType::Commune_SCHS, false, ['63113']);
        $this->updatePartner($territory63, 'SCHS Royat', PartnerType::Commune_SCHS, false, ['63308']);
        $this->updatePartner($territory63, 'TDM', PartnerType::EPCI, false, ['63430', '63008', '63015', '63016', '63066', '63072', '63095', '63102', '63125', '63138', '63151', '63184', '63231', '63249', '63253', '63260', '63267', '63271', '63291', '63298', '63301', '63343', '63393', '63402', '63310', '63414', '63418', '63463', '63468', '63469']);
        $this->updatePartner($territory63, 'Urbanis', PartnerType::Operateur_visites_et_travaux, false, null);
        $this->updatePartner($territory63, 'DDETS', PartnerType::DDETS, false, null);

        $this->io->section('<info>Update partners in Yonne</info>');
        $territory89 = $this->territoryRepository->findOneBy(['zip' => '89']);
        $this->updatePartner($territory89, 'ADIL', PartnerType::ADIL, true, null);
        $this->updatePartner($territory89, 'DDT', PartnerType::DDT_M, false, null);
        $this->updatePartner($territory89, 'CAF', PartnerType::CAF_MSA, true, null);
        $this->updatePartner($territory89, 'ARS', PartnerType::ARS, false, null);
        $this->updatePartner($territory89, 'CD', PartnerType::Conseil_departemental, false, null);
        $this->updatePartner($territory89, 'URBANIS', PartnerType::Operateur_visites_et_travaux, false, null);
        $this->updatePartner($territory89, 'SOLIHA89', PartnerType::Operateur_visites_et_travaux, false, null);
        $this->updatePartner($territory89, 'Mairie de Tonnerre', PartnerType::Commune_SCHS, false, ['89418']);
        $this->updatePartner($territory89, 'Mairie de Avallon', PartnerType::Commune_SCHS, false, ['89025']);
        $this->updatePartner($territory89, 'CAGS', PartnerType::Commune_SCHS, false, ['89387']);
        $this->updatePartner($territory89, 'DDTESPP', PartnerType::DDETS, false, null);
        $this->updatePartner($territory89, 'SCHS Auxerre', PartnerType::Commune_SCHS, false, ['89000']);
        $this->updatePartner($territory89, "Maison de l'Habitat du Jovinien", PartnerType::Commune_SCHS, false, ['89300']);
        $this->updatePartner($territory89, 'Mairie de Saint Florentin', PartnerType::Commune_SCHS, false, ['89345']);
        $this->updatePartner($territory89, 'ADIL MISSION CAF', PartnerType::ADIL, true, null);
        $this->updatePartner($territory89, "Mairie d'Egriselles le Bocage", PartnerType::Commune_SCHS, false, ['89151']);
        $this->updatePartner($territory89, "Mairie d'Epineau les voves", PartnerType::Commune_SCHS, false, ['89152']);
        $this->updatePartner($territory89, 'Mairie de Saint Fargeau', PartnerType::Commune_SCHS, false, ['89344']);
        $this->updatePartner($territory89, 'Mairie de Saint Sauveur en Puisaye', PartnerType::Commune_SCHS, false, ['89368']);
        $this->updatePartner($territory89, 'Mairie de Charbuy', PartnerType::Commune_SCHS, false, ['89083']);
        $this->updatePartner($territory89, 'Mairie de Tronchoy', PartnerType::Commune_SCHS, false, ['89423']);

        $this->io->success('Partners and communes updated');

        return Command::SUCCESS;
    }

    private function updatePartner(Territory $territory, string $nomPartner, PartnerType $typePartner, bool $isNonDecence, ?array $insee = null)
    {
        $partner = $this->partnerRepository->findOneBy(['nom' => $nomPartner, 'territory' => $territory]);
        if ($partner) {
            $partner->setType($typePartner);
            $info = '<info>Update partner '.$partner->getNom().' </info> type : '.$partner->getType()->name;
            if ($isNonDecence) {
                $qualification = [Qualification::Non_decence_energetique];
                $partner->setCompetence($qualification);
                $info .= ' and competence : '.Qualification::Non_decence_energetique->name;
            }
            if ($insee) {
                $partner->setInsee($insee);
                $info .= ' and inseecodes : '.implode(',', $insee);
            }
            $this->io->text($info);
            $this->entityManager->persist($partner);
        } else {
            $this->io->warning('No partner with name : '.$nomPartner.' in territory '.$territory->getName());
        }
        $this->entityManager->flush();
    }

    private function updateCommunes(array $inseeCommunes)
    {
        foreach ($inseeCommunes as $codeInsee) {
            $commune = $this->communeRepository->findOneBy(['codeInsee' => $codeInsee]);
            if ($commune) {
                $this->io->text('<info>Update commune setIsZonePermisLouer </info> : '.$commune->getNom());
                $commune->setIsZonePermisLouer(true);
                $this->entityManager->persist($commune);
            } else {
                $this->io->warning('No commune with insee code : '.$codeInsee);
            }
        }
        $this->entityManager->flush();
    }
}
