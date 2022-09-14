<?php

namespace App\Command;

use App\Entity\Affectation;
use App\Entity\Config;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Entity\Tag;
use App\Entity\Territory;
use App\Entity\User;
use App\EventListener\ActivityListener;
use App\Service\NotificationService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'app:migrate-legacy',
    description: 'Migrate Legacy plateform',
)]
class MigrateLegacyCommand extends Command
{
    public const LEGACY_TERRITORY = [
        '81', '08', '29', '69', '71', '63', '47', '19', '2A', '31', '59', '64', '04', '06', '13',
    ];

    public const TERRITORIES_WITHOUT_PARTNER_HISTOLOGE = ['31', '64', '71'];

    private Connection|null $connection;
    private Territory $territory;
    private array $results;
    private Table $table;
    private $mapping;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private ActivityListener $activityListener,
        private NotificationService $notificationService,
        private RouterInterface $router,
        private string $hostUrl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('territory_zip', InputArgument::REQUIRED, 'The territory of legacy platform');
        $this->addOption(
            'notify',
            null,
            InputOption::VALUE_OPTIONAL,
            'Would you like to notify the users? (y/n)',
            'no'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->table = new Table($output);
        $this->table->setHeaders(['Tables', 'Lines inserted', 'Total']);
        parent::initialize($input, $output); // TODO: Change the autogenerated stub
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->entityManager->getEventManager()->removeEventSubscriber($this->activityListener);
        $io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument('territory_zip');
        if (!\in_array($territoryZip, self::LEGACY_TERRITORY)) {
            $io->error(sprintf('%s is not legacy territory', $territoryZip));

            return Command::FAILURE;
        }

        $io->info(sprintf('You passed an argument: %s', $territoryZip));
        $this->territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => $territoryZip]);

        /* @var Connection $connection */
        $this->connection = $this->managerRegistry->getConnection('legacy_'.$territoryZip);
        $this->connection->connect();

        $io->info('Migrate table...');
        $this->loadConfig();
        $this->loadPartner();
        $this->loadUser();
        $this->loadSignalement();
        $this->loadSuivi();
        $this->loadAffectation();
        $this->loadTag();
        $this->loadSignalementCritere();
        $this->loadSignalementCriticite();
        $this->loadSignalementSituation();
        $this->loadTagSignalement();

        $this->table->render();
        $io->success('line has been imported');
        $this->entityManager->getEventManager()->addEventSubscriber($this->activityListener);

        if ('yes' === $input->getOption('notify')) {
            $nbUserMailSent = $this->sendMailResetPassword();
            $io->success($nbUserMailSent.' user(s) has/have been notified');
        } else {
            $io->info('Users have not been notified, please add option --notify=yes');
        }

        return Command::SUCCESS;
    }

    private function loadConfig(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from config');
        $legacyConfigList = $statement->executeQuery()->fetchAllAssociative();

        $i = 0;
        foreach ($legacyConfigList as $legacyConfig) {
            $config = $this->entityManager->getRepository(Config::class)->findOneBy(
                ['nomTerritoire' => mb_strtoupper($legacyConfig['nom_territoire'])]
            );

            if (null === $config) {
                $config = new Config();
                ++$i;
            }
            $config
                ->setNomTerritoire(mb_strtoupper($legacyConfig['nom_territoire']))
                ->setUrlTerritoire($legacyConfig['url_territoire'])
                ->setNomDpo($legacyConfig['nom_dpo'])
                ->setMailDpo($legacyConfig['mail_dpo'])
                ->setNomResponsable($legacyConfig['nom_responsable'])
                ->setMailResponsable($legacyConfig['mail_responsable'])
                ->setAdresseDpo($legacyConfig['adresse_dpo'])
                ->setLogotype($legacyConfig['logotype'])
                ->setEmailReponse($legacyConfig['email_reponse'])
                ->setTrackingCode($legacyConfig['tracking_code'])
                ->setTagManagerCode($legacyConfig['tag_manager_code'])
                ->setMailAr($legacyConfig['mail_ar'])
                ->setMailValidation($legacyConfig['mail_validation'])
                ->setEsaboraToken($legacyConfig['esabora_token'])
                ->setEsaboraUrl($legacyConfig['esabora_url'])
                ->setTelContact($legacyConfig['tel_contact']);

            $this->entityManager->persist($config);
        }

        $this->entityManager->flush();
        $total = $this->entityManager->getRepository(Config::class)->count([]);
        $this->table->addRow(['config', $i, $total]);
    }

    private function loadPartner(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare("SELECT * from partenaire where nom not like '%Histologe%'");
        $legacyPartnerList = $statement->executeQuery()->fetchAllAssociative();

        $i = 0;
        foreach ($legacyPartnerList as $legacyPartner) {
            $partner = $this->entityManager->getRepository(Partner::class)->findOneBy(
                ['nom' => $legacyPartner['nom'], 'territory' => $this->territory]
            );

            if (null === $partner) {
                $partner = new Partner();
                ++$i;
            }

            $partner
                ->setTerritory($this->territory)
                ->setNom($legacyPartner['nom'])
                ->setIsArchive((bool) $legacyPartner['is_archive'])
                ->setIsCommune((bool) $legacyPartner['is_commune'])
                ->setInsee(json_decode($legacyPartner['insee']))
                ->setEmail($legacyPartner['email'])
                ->setEsaboraUrl($legacyPartner['esabora_url'])
                ->setEsaboraToken($legacyPartner['esabora_token']);

            $this->entityManager->persist($partner);
            $this->mapping['partner'][$legacyPartner['id']] = $legacyPartner['nom'];
        }

        $this->entityManager->flush();
        $total = $this->entityManager->getRepository(Partner::class)->count([]);
        $this->table->addRow(['partner', $i, $total]);
    }

    private function loadUser(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare("SELECT * from user where email not like '%beta.gouv.fr%'");
        $legacyUserList = $statement->executeQuery()->fetchAllAssociative();

        $i = 0;
        foreach ($legacyUserList as $legacyUser) {
            if ('1' === $legacyUser['partenaire_id'] &&
                !\in_array($this->territory->getZip(), self::TERRITORIES_WITHOUT_PARTNER_HISTOLOGE)
            ) {
                $partner = $partner = $this->entityManager->getRepository(Partner::class)->find((int) $legacyUser['partenaire_id']);
            } else {
                $partner = $this->entityManager->getRepository(Partner::class)->findOneBy([
                    'nom' => $this->mapping['partner'][$legacyUser['partenaire_id']],
                    'territory' => $this->territory,
                ]);
            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $legacyUser['email']]);
            if (null === $user) {
                $user = new User();
                ++$i;
            }

            $user
                ->setEmail($legacyUser['email'])
                ->setTerritory('1' !== $legacyUser['partenaire_id'] ? $this->territory : null)
                ->setRoles('1' !== $legacyUser['partenaire_id'] ? json_decode($legacyUser['roles'], true) : [User::ROLES['Super Admin']])
                ->setPartner($partner)
                ->setNom($legacyUser['nom'])
                ->setPrenom($legacyUser['prenom'])
                ->setStatut((int) $legacyUser['statut'])
                ->setLastLoginAt(new \DateTimeImmutable($legacyUser['last_login_at']))
                ->setIsGenerique((bool) $legacyUser['is_generique'])
                ->setIsMailingActive((bool) $legacyUser['is_mailing_active']);

            $this->entityManager->persist($user);
            $this->mapping['user'][$legacyUser['id']] = $legacyUser['email'];
        }
        $this->entityManager->flush();
        $total = $this->entityManager->getRepository(User::class)->count([]);
        $this->table->addRow(['user', $i, $total]);
    }

    private function loadSignalement(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from signalement');
        $legacySignalementList = $statement->executeQuery()->fetchAllAssociative();
        $i = 0;
        foreach ($legacySignalementList as $legacySignalement) {
            $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
                    'uuid' => $legacySignalement['uuid'],
                    'territory' => $this->territory, ]
            );

            if (null === $signalement) {
                $signalement = new Signalement();
                ++$i;
            }

            $signalement
                ->setTerritory($this->territory)
                ->setDetails($legacySignalement['details'])
                ->setPhotos(json_decode($legacySignalement['photos'], true))
                ->setDocuments(json_decode($legacySignalement['documents'], true))
                ->setIsProprioAverti((bool) $legacySignalement['is_proprio_averti'])
                ->setNbAdultes($legacySignalement['nb_adultes'])
                ->setNbEnfantsM6($legacySignalement['nb_enfants_m6'])
                ->setNbEnfantsP6($legacySignalement['nb_enfants_p6'])
                ->setIsAllocataire($legacySignalement['is_allocataire'])
                ->setNumAllocataire($legacySignalement['num_allocataire'])
                ->setNatureLogement(mb_strtolower($legacySignalement['nature_logement']))
                ->setTypeLogement($legacySignalement['type_logement'])
                ->setSuperficie($legacySignalement['superficie'])
                ->setLoyer($legacySignalement['loyer'])
                ->setIsBailEnCours((bool) $legacySignalement['is_bail_en_cours'])
                ->setDateEntree(new \DateTimeImmutable($legacySignalement['date_entree']))
                ->setNomProprio($legacySignalement['nom_proprio'])
                ->setAdresseProprio($legacySignalement['adresse_proprio'])
                ->setTelProprio($legacySignalement['tel_proprio'])
                ->setMailProprio($legacySignalement['mail_proprio'])
                ->setIsLogementSocial((bool) $legacySignalement['is_logement_social'])
                ->setIsPreavisDepart((bool) $legacySignalement['is_preavis_depart'])
                ->setIsRelogement((bool) $legacySignalement['is_relogement'])
                ->setIsRefusIntervention($legacySignalement['is_refus_intervention'])
                ->setRaisonRefusIntervention($legacySignalement['raison_refus_intervention'])
                ->setIsNotOccupant((bool) $legacySignalement['is_not_occupant'])
                ->setNomDeclarant($legacySignalement['nom_declarant'])
                ->setPrenomDeclarant($legacySignalement['prenom_declarant'])
                ->setTelDeclarant($legacySignalement['tel_declarant'])
                ->setMailDeclarant($legacySignalement['mail_declarant'])
                ->setStructureDeclarant($legacySignalement['structure_declarant'])
                ->setNomOccupant($legacySignalement['nom_occupant'])
                ->setPrenomOccupant($legacySignalement['prenom_occupant'])
                ->setTelOccupant($legacySignalement['tel_occupant'])
                ->setMailOccupant($legacySignalement['mail_occupant'])
                ->setAdresseOccupant($legacySignalement['adresse_occupant'])
                ->setCpOccupant($legacySignalement['cp_occupant'])
                ->setVilleOccupant($legacySignalement['ville_occupant'])
                ->setIsCguAccepted((bool) $legacySignalement['is_cgu_accepted']);

            $statement = $this->connection->prepare(
                'select email from user where id = '.(int) $legacySignalement['modified_by_id']
            );
            $email = $statement->executeQuery()->fetchOne();
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            $signalement->setModifiedBy($user)
                ->setCreatedAt(new \DateTimeImmutable($legacySignalement['created_at']))
                ->setModifiedAt(new \DateTimeImmutable($legacySignalement['modified_at']))
                ->setStatut((int) $legacySignalement['statut'])
                ->setReference($legacySignalement['reference'])
                ->setJsonContent(json_decode($legacySignalement['json_content'], true))
                ->setGeoloc(json_decode($legacySignalement['geoloc'], true))
                ->setUuid($legacySignalement['uuid'])
                ->setDateVisite(new \DateTimeImmutable($legacySignalement['date_visite']))
                ->setIsOccupantPresentVisite((bool) $legacySignalement['is_occupant_present_visite'])
                ->setMontantAllocation((float) $legacySignalement['montant_allocation'])
                ->setIsSituationHandicap($legacySignalement['is_situation_handicap'])
                ->setCodeProcedure($legacySignalement['code_procedure'])
                ->setScoreCreation((float) $legacySignalement['score_creation'])
                ->setScoreCloture((float) $legacySignalement['score_cloture'])
                ->setEtageOccupant($legacySignalement['etage_occupant'])
                ->setEscalierOccupant($legacySignalement['escalier_occupant'])
                ->setNumAppartOccupant($legacySignalement['num_appart_occupant'])
                ->setAdresseAutreOccupant($legacySignalement['adresse_autre_occupant'])
                ->setModeContactProprio(json_decode($legacySignalement['mode_contact_proprio']))
                ->setInseeOccupant($legacySignalement['insee_occupant'])
                ->setCodeSuivi($legacySignalement['code_suivi'])
                ->setLienDeclarantOccupant($legacySignalement['lien_declarant_occupant'])
                ->setIsConsentementTiers((bool) $legacySignalement['is_consentement_tiers'])
                ->setValidatedAt(new \DateTimeImmutable($legacySignalement['validated_at']))
                ->setIsRsa((bool) $legacySignalement['is_rsa'])
                ->setProprioAvertiAt(new \DateTimeImmutable($legacySignalement['prorio_averti_at']))
                ->setAnneeConstruction((int) $legacySignalement['annee_construction'])
                ->setTypeEnergieLogement($legacySignalement['type_energie_logement'])
                ->setOrigineSignalement($legacySignalement['origine_signalement'])
                ->setSituationOccupant($legacySignalement['situation_occupant'])
                ->setSituationProOccupant($legacySignalement['situation_pro_occupant'])
                ->setNaissanceOccupantAt(new \DateTimeImmutable($legacySignalement['naissance_occupant_at']))
                ->setIsLogementCollectif((bool) $legacySignalement['is_logement_collectif'])
                ->setIsConstructionAvant1949((bool) $legacySignalement['is_construction_avant1949'])
                ->setIsDiagSocioTechnique((bool) $legacySignalement['is_diag_socio_technique'])
                ->setIsRisqueSurOccupation((bool) $legacySignalement['is_risque_sur_occupation'])
                ->setProprioAvertiAt(new \DateTimeImmutable($legacySignalement['proprio_averti_at']))
                ->setNomReferentSocial($legacySignalement['nom_referent_social'])
                ->setStructureReferentSocial($legacySignalement['structure_referent_social'])
                ->setMailSyndic($legacySignalement['mail_syndic'])
                ->setNomSci($legacySignalement['nom_sci'])
                ->setNomRepresentantSci($legacySignalement['nom_representant_sci'])
                ->setTelSci($legacySignalement['tel_sci'])
                ->setMailSci($legacySignalement['mail_sci'])
                ->setTelSyndic($legacySignalement['tel_syndic'])
                ->setNomSyndic($legacySignalement['nom_syndic'])
                ->setNumeroInvariant($legacySignalement['numero_invariant'])
                ->setNbPiecesLogement((int) $legacySignalement['nb_pieces_logement'])
                ->setNbChambresLogement((int) $legacySignalement['nb_chambres_logement'])
                ->setNbNiveauxLogement((int) $legacySignalement['nb_niveaux_logement'])
                ->setNbOccupantsLogement((int) $legacySignalement['nb_occupants_logement'])
                ->setMotifCloture((int) $legacySignalement['motif_cloture'])
                ->setClosedAt(new \DateTimeImmutable($legacySignalement['closed_at']))
                ->setTelOccupantBis($legacySignalement['tel_occupant_bis'])
                ->setIsFondSolidariteLogement((bool) $legacySignalement['is_fond_solidarite_logement']);

            $this->entityManager->persist($signalement);
            $this->mapping['signalement'][$legacySignalement['id']] = $legacySignalement['uuid'];
        }
        $this->entityManager->flush();
        $total = $this->entityManager->getRepository(Signalement::class)->count([]);
        $this->table->addRow(['signalement', $i, $total]);
    }

    private function loadSuivi(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from suivi');
        $legacySuiviList = $statement->executeQuery()->fetchAllAssociative();
        $i = 0;
        foreach ($legacySuiviList as $legacySuivi) {
            $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
                    'uuid' => $this->mapping['signalement'][$legacySuivi['signalement_id']],
                    'territory' => $this->territory,
                ]
            );

            $statement = $this->connection->prepare(
                'select email from user where id = '.(int) $legacySuivi['created_by_id']
            );
            $email = $statement->executeQuery()->fetchOne();
            $createdBy = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            $suivi = $this->entityManager->getRepository(Suivi::class)->findOneBy([
                'signalement' => $signalement,
                'createdBy' => $createdBy,
                'createdAt' => new \DateTimeImmutable($legacySuivi['created_at']),
            ]);

            if (null === $suivi) {
                $suivi = new Suivi();
                ++$i;
            }
            $suivi
                ->setCreatedBy($createdBy)
                ->setCreatedAt(new \DateTimeImmutable($legacySuivi['created_at']))
                ->setSignalement($signalement)
                ->setDescription($legacySuivi['description'])
                ->setIsPublic((bool) $legacySuivi['is_public']);

            $this->entityManager->persist($suivi);
        }
        $this->entityManager->flush();

        $total = $this->entityManager->getRepository(Suivi::class)->count([]);
        $this->table->addRow(['suivi', $i, $total]);
    }

    private function loadAffectation(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from affectation');
        $legacyAffectationList = $statement->executeQuery()->fetchAllAssociative();
        $i = 0;
        foreach ($legacyAffectationList as $legacyAffectation) {
            $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
                    'uuid' => $this->mapping['signalement'][$legacyAffectation['signalement_id']],
                    'territory' => $this->territory,
                ]
            );
            $partner = $this->entityManager->getRepository(Partner::class)->findOneBy([
                    'nom' => $this->mapping['partner'][(int) $legacyAffectation['partenaire_id']],
                    'territory' => $this->territory,
                ]
            );

            $statement = $this->connection->prepare(
                'select email from user where id = '.(int) $legacyAffectation['answered_by_id']
            );
            $email = $statement->executeQuery()->fetchOne();
            $answerdBy = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            $statement = $this->connection->prepare(
                'select email from user where id = '.(int) $legacyAffectation['affected_by_id']
            );
            $email = $statement->executeQuery()->fetchOne();
            $affectedBy = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            $affectation = $this->entityManager->getRepository(Affectation::class)->findOneBy([
                'signalement' => $signalement, 'partner' => $partner,
            ]);

            if (null === $affectation) {
                $affectation = new Affectation();
                ++$i;
            }

            $affectation
                ->setTerritory($this->territory)
                ->setSignalement($signalement)
                ->setPartner($partner)
                ->setAnsweredAt(new \DateTimeImmutable($legacyAffectation['answered_at']))
                ->setCreatedAt(new \DateTimeImmutable($legacyAffectation['created_at']))
                ->setStatut($legacyAffectation['statut'])
                ->setAnsweredBy($answerdBy)
                ->setAffectedBy($affectedBy)
                ->setMotifCloture($legacyAffectation['motif_cloture']);

            $this->entityManager->persist($affectation);
        }

        $this->entityManager->flush();

        $total = $this->entityManager->getRepository(Affectation::class)->count([]);
        $this->table->addRow(['affectation', $i, $total]);
    }

    private function loadTag(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from tag');
        $legacyTagList = $statement->executeQuery()->fetchAllAssociative();
        $i = 0;
        foreach ($legacyTagList as $legacyTag) {
            $tag = $this->entityManager->getRepository(Tag::class)->findOneBy([
                'territory' => $this->territory,
                'label' => $legacyTag['label'],
            ]);
            if (null === $tag) {
                $tag = new Tag();
                ++$i;
            }
            $tag
                ->setTerritory($this->territory)
                ->setIsArchive($legacyTag['is_archive'])
                ->setLabel($legacyTag['label']);

            $this->entityManager->persist($tag);
        }
        $this->entityManager->flush();
        $total = $this->entityManager->getRepository(Tag::class)->count([]);
        $this->table->addRow(['tag', $i, $total]);
    }

    private function loadSignalementCritere(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from signalement_critere');
        $legacySignalementCritereList = $statement->executeQuery()->fetchAllAssociative();
        $i = 0;
        foreach ($legacySignalementCritereList as $legacySignalementCritere) {
            /** @var Critere $critere */
            $critere = $this->entityManager->getRepository(Critere::class)->find((int) $legacySignalementCritere['critere_id']);
            $signalementUuid = $this->mapping['signalement'][(int) $legacySignalementCritere['signalement_id']];
            /** @var Signalement $signalement */
            $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
                'uuid' => $signalementUuid,
                'territory' => $this->territory,
            ]);

            if (null !== $critere && !$signalement->getCriteres()->contains($critere)) {
                $signalement->addCritere($critere);
                $this->entityManager->persist($signalement);
                ++$i;
            }
        }
        $this->entityManager->flush();

        $signalements = $this->entityManager->getRepository(Signalement::class)->findAll();
        /** @var Signalement $signalement */
        $total = 0;
        foreach ($signalements as $signalement) {
            $total += \count($signalement->getCriteres());
        }
        $this->table->addRow(['signalement_critere', $i, $total]);
    }

    private function loadSignalementCriticite(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from signalement_criticite');
        $legacySignalementCriticiteList = $statement->executeQuery()->fetchAllAssociative();
        $i = 0;
        foreach ($legacySignalementCriticiteList as $legacySignalementCriticite) {
            /** @var Criticite criticite */
            $criticite = $this->entityManager->getRepository(Criticite::class)->find(
                (int) $legacySignalementCriticite['criticite_id']
            );
            $signalementUuid = $this->mapping['signalement'][(int) $legacySignalementCriticite['signalement_id']];
            /** @var Signalement $signalement */
            $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
                'uuid' => $signalementUuid,
                'territory' => $this->territory,
            ]);

            if (null !== $criticite && !$signalement->getCriticites()->contains($criticite)) {
                $signalement->addCriticite($criticite);
                $this->entityManager->persist($signalement);
                ++$i;
            }
        }
        $this->entityManager->flush();

        $signalements = $this->entityManager->getRepository(Signalement::class)->findAll();
        /** @var Signalement $signalement */
        $total = 0;
        foreach ($signalements as $signalement) {
            $total += \count($signalement->getCriticites());
        }
        $this->table->addRow(['signalement_criticite', $i, $total]);
    }

    private function loadSignalementSituation(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from signalement_situation');
        $legacySignalementSituationList = $statement->executeQuery()->fetchAllAssociative();
        $i = 0;
        foreach ($legacySignalementSituationList as $legacySignalementSituation) {
            /** @var Situation $situation */
            $situation = $this->entityManager->getRepository(Situation::class)->find(
                (int) $legacySignalementSituation['situation_id']
            );
            $signalementUuid = $this->mapping['signalement'][(int) $legacySignalementSituation['signalement_id']];
            /** @var Signalement $signalement */
            $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
                'uuid' => $signalementUuid,
                'territory' => $this->territory,
            ]);

            if (null !== $situation && !$signalement->getSituations()->contains($situation)) {
                $signalement->addSituation($situation);
                $this->entityManager->persist($signalement);
                ++$i;
            }
        }
        $this->entityManager->flush();

        $signalements = $this->entityManager->getRepository(Signalement::class)->findAll();
        /** @var Signalement $signalement */
        $total = 0;
        foreach ($signalements as $signalement) {
            $total += \count($signalement->getSituations());
        }
        $this->table->addRow(['signalement_situation', $i, $total]);
    }

    private function loadTagSignalement(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->prepare('SELECT * from tag_signalement');
        $legacySignalementTagList = $statement->executeQuery()->fetchAllAssociative();
        $i = 0;
        foreach ($legacySignalementTagList as $legacySignalementTag) {
            /** @var Tag $tag */
            $tag = $this->entityManager->getRepository(Tag::class)->find(
                (int) $legacySignalementTag['tag_id']
            );
            $signalementUuid = $this->mapping['signalement'][(int) $legacySignalementTag['signalement_id']];
            /** @var Signalement $signalement */
            $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
                'uuid' => $signalementUuid,
                'territory' => $this->territory,
            ]);

            if (null !== $tag && !$signalement->getTags()->contains($tag)) {
                $signalement->addTag($tag);
                $this->entityManager->persist($tag);
                ++$i;
            }
        }
        $this->entityManager->flush();

        $signalements = $this->entityManager->getRepository(Signalement::class)->findAll();
        /** @var Signalement $signalement */
        $total = 0;
        foreach ($signalements as $signalement) {
            $total += \count($signalement->getTags());
        }
        $this->table->addRow(['signalement_tag', $i, $total]);
    }

    private function sendMailResetPassword(): int
    {
        $users = $this->entityManager->getRepository(User::class)->findBy([
            'territory' => $this->territory,
        ]);
        $i = 0;
        /** @var User $user */
        foreach ($users as $user) {
            $this->notificationService->send(
                NotificationService::TYPE_MIGRATION_PASSWORD,
                $user->getEmail(),
                ['link' => $this->hostUrl.$this->router->generate('login_mdp_perdu')],
                $user->getTerritory()
            );
            ++$i;
        }

        return $i;
    }
}
