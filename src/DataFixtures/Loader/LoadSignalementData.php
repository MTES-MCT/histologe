<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\UserStatus;
use App\Entity\File;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Factory\Signalement\InformationComplementaireFactory;
use App\Factory\Signalement\InformationProcedureFactory;
use App\Factory\Signalement\SituationFoyerFactory;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use App\Manager\UserManager;
use App\Repository\BailleurRepository;
use App\Repository\CriticiteRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\SignalementDraftRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\Security\PartnerAuthorizedResolver;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class LoadSignalementData extends Fixture implements OrderedFixtureInterface
{
    private ?User $admin = null;

    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        private readonly BailleurRepository $bailleurRepository,
        private readonly CriticiteRepository $criticiteRepository,
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly SignalementDraftRepository $signalementDraftRepository,
        private readonly TagRepository $tagRepository,
        private readonly UserRepository $userRepository,
        private readonly FileFactory $fileFactory,
        private readonly UserManager $userManager,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $this->admin = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
        $signalementRows = Yaml::parseFile(__DIR__.'/../Files/Signalement.yml');
        foreach ($signalementRows['signalements'] as $row) {
            $this->loadSignalements($manager, $row);
        }
        $newSignalementRows = Yaml::parseFile(__DIR__.'/../Files/NewSignalement.yml');
        foreach ($newSignalementRows['signalements'] as $row) {
            $this->loadNewSignalements($manager, $row);
        }

        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws \Exception
     */
    private function loadSignalements(ObjectManager $manager, array $row): void
    {
        $faker = Factory::create('fr_FR');
        $phoneNumber = $row['phone_number'];

        $signalement = (new Signalement())
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setProfileDeclarant(ProfileDeclarant::from($row['profile_declarant']))
            ->setNomOccupant($row['nom_occupant'] ?? $faker->lastName())
            ->setPrenomOccupant($faker->firstName())
            ->setTelOccupant($phoneNumber)
            ->setAdresseOccupant($row['adresse_occupant'] ?? str_replace(',', '', $faker->streetAddress()))
            ->setVilleOccupant($row['ville_occupant'])
            ->setCpOccupant($row['cp_occupant'])
            ->setInseeOccupant($row['insee_occupant'])
            ->setNbOccupantsLogement($row['nb_occupants_logement'])
            ->setNbAdultes($row['nb_adultes'])
            ->setNbEnfantsM6($row['nb_enfants_m6'])
            ->setNbEnfantsP6($row['nb_enfants_p6'])
            ->setMailOccupant($row['mail_occupant'] ?? $faker->email())
            ->setNumAppartOccupant((string) $faker->randomNumber(3))
            ->setNatureLogement($row['nature_logement'])
            ->setSuperficie($row['superficie'])
            ->setDetails($row['details'])
            ->setIsProprioAverti($row['is_proprio_averti'])
            ->setNomProprio($faker->company())
            ->setPrenomProprio($faker->firstName())
            ->setMailProprio($faker->companyEmail)
            ->setTelProprio($phoneNumber)
            ->setAdresseProprio($faker->streetAddress())
            ->setCodePostalProprio($faker->postcode())
            ->setVilleProprio($faker->city())
            ->setIsCguAccepted(true)
            ->setIsAllocataire($row['is_allocataire'] ?? null)
            ->setNumAllocataire((string) $faker->randomNumber(6))
            ->setStatut(SignalementStatus::from($row['statut']))
            ->setScore($row['score'])
            ->setReference($row['reference'])
            ->setIsBailEnCours(false)
            ->setIsRelogement($row['is_relogement'] ?? false)
            ->setIsLogementSocial($row['is_logement_social'] ?? null)
            ->setIsPreavisDepart($row['is_preavis_depart'] ?? false)
            ->setIsRefusIntervention(false)
            ->setGeoloc(json_decode($row['geoloc'], true))
            ->setIsRsa(false)
            ->setCodeSuivi($row['code_suivi'] ?? $faker->uuid())
            ->setUuid($row['uuid'])
            ->setSituationOccupant($row['situation_occupant'] ?? null)
            ->setValidatedAt(SignalementStatus::ACTIVE->value === $row['statut'] ? new \DateTimeImmutable() : null)
            ->setOrigineSignalement($row['origine_signalement'] ?? null)
            ->setCreatedAt(
                isset($row['created_at'])
                    ? new \DateTimeImmutable($row['created_at'])
                    : (new \DateTimeImmutable())->modify('-15 days')
            )
            ->setIsUsagerAbandonProcedure($row['usager_abandon_procedure'] ?? null);
        if (isset($row['is_not_occupant'])) {
            $linkChoices = OccupantLink::getLabelList();
            $signalement
                ->setIsNotOccupant((bool) $row['is_not_occupant'])
                ->setNomDeclarant($row['nom_declarant'] ?? $faker->lastName())
                ->setPrenomDeclarant($row['prenom_declarant'] ?? $faker->firstName())
                ->setTelDeclarant($row['tel_declarant'] ?? $phoneNumber)
                ->setMailDeclarant($row['mail_declarant'] ?? $faker->email())
                ->setStructureDeclarant($faker->company())
                ->setLienDeclarantOccupant($linkChoices[array_rand($linkChoices)]);
        } else {
            $signalement->setIsNotOccupant(false);
        }

        if (isset($row['nature_logement_autre_precision'])) {
            $typeCompositionLogement = new TypeCompositionLogement();
            $typeCompositionLogement->setTypeLogementNatureAutrePrecision(
                $row['nature_logement_autre_precision']
            );
            $signalement->setTypeCompositionLogement($typeCompositionLogement);
        }

        if (isset($row['is_imported'])) {
            $signalement
                ->setIsImported($row['is_imported'])
                ->setModifiedAt(null);
        }

        if (isset($row['date_entree'])) {
            $signalement->setDateEntree(new \DateTimeImmutable($row['date_entree']));
        }

        if (isset($row['adresse_autre_occupant'])) {
            $signalement->setAdresseAutreOccupant($row['adresse_autre_occupant']);
        }

        if (isset($row['montant_allocation'])) {
            $signalement->setMontantAllocation($row['montant_allocation']);
        }

        if (SignalementStatus::CLOSED->value === $row['statut']) {
            $signalement
                ->setMotifCloture(MotifCloture::tryFrom($row['motif_cloture']))
                ->setClosedAt(new \DateTimeImmutable())
                ->setClosedBy($this->userRepository->findOneBy(['statut' => UserStatus::ACTIVE]));
        }

        if (SignalementStatus::REFUSED->value === $row['statut']) {
            $signalement
                ->setMotifRefus(MotifRefus::tryFrom($row['motif_refus']));
        }

        if (isset($row['tags'])) {
            foreach ($row['tags'] as $tag) {
                $signalement->addTag($this->tagRepository->findOneBy(['label' => $tag, 'territory' => $signalement->getTerritory()]));
            }
        }

        if (isset($row['criticites'])) {
            foreach ($row['criticites'] as $criticite) {
                $signalement->addCriticite($this->criticiteRepository->findOneBy(['label' => $criticite]));
            }
        }

        if (isset($row['bailleur'])) {
            $signalement->setBailleur($this->bailleurRepository->findOneBailleurBy($row['bailleur'], $signalement->getTerritory()));
        }

        if (isset($row['synchro_data'])) {
            $signalement->setSynchroData($row['synchro_data'], 'idoss');
        }

        $manager->persist($signalement);
        $this->userManager->createUsagerFromSignalement($signalement);
        $this->userManager->createUsagerFromSignalement($signalement, $this->userManager::DECLARANT);

        if (isset($row['qualifications'])) {
            foreach ($row['qualifications'] as $qualificationLabel) {
                $signalementQualification = $this->buildSignalementQualification(
                    $signalement,
                    $row,
                    $qualificationLabel
                );

                $manager->persist($signalementQualification);

                $signalement->addSignalementQualification($signalementQualification);
                $manager->persist($signalement);
            }
        }

        $documentsAndPhotos = [
            [
                'file' => 'test1.23.pdf',
                'titre' => 'Fiche reperage.pdf',
                'user' => 'api-01@signal-logement.fr',
                'mimeType' => 'application/pdf',
            ],
            [
                'file' => 'test1.pdf',
                'titre' => 'Compte rendu de visite.pdf',
                'user' => 'user-69-05@signal-logement.fr',
                'mimeType' => 'application/pdf',
            ],
            [
                'file' => 'blank-'.$row['reference'].'.pdf',
                'titre' => 'Blank.pdf',
                'user' => 'api-01@signal-logement.fr',
                'mimeType' => 'application/pdf',
            ],
            [
                'file' => 'Capture-d-ecran-du-2023-06-13-12-58-43-648b2a6b9730f.png',
                'titre' => '20220520_112424.png',
                'user' => 'api-01@signal-logement.fr',
                'mimeType' => 'image/png',
            ],
            [
                'file' => 'Capture-d-ecran-du-2023-04-07-15-27-36-64302a1b57a20.png',
                'titre' => 'IMG_20230220_141432735_HDR.png',
                'user' => 'user-69-06@signal-logement.fr',
                'mimeType' => 'image/png',
            ],
            [
                'file' => 'blank-'.$row['reference'].'.jpg',
                'titre' => 'Blank.jpg',
                'user' => 'api-01@signal-logement.fr',
                'mimeType' => 'image/jpeg',
            ],
        ];

        $dateMinusTwoMonth = (new \DateTimeImmutable())->modify('-2 months');
        foreach ($documentsAndPhotos as $document) {
            $user = $this->userRepository->findOneBy(['email' => $document['user']]);
            $partner = null;
            if ($user) {
                if ($user->isApiUser()) {
                    $partners = $this->partnerAuthorizedResolver->resolveBy($user);
                    $partner = array_shift($partners);
                } else {
                    $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
                }
            }
            $file = $this->fileFactory->createInstanceFrom(
                filename: $document['file'],
                title: $document['titre'],
                signalement: $signalement,
                partner: $partner,
                user: $user,
                documentType: DocumentType::AUTRE
            );
            if (\in_array($document['mimeType'], File::RESIZABLE_MIME_TYPES)) {
                $file->setIsVariantsGenerated(true);
                $file->setCreatedAt($dateMinusTwoMonth);
            }
            $manager->persist($file);
        }

        if ('2022-4' === $row['reference']) {
            $countMorePhoto = 1;
            $user = $this->userRepository->findOneBy(['id' => 1]);
            while ($countMorePhoto < 12) {
                $file = $this->fileFactory->createInstanceFrom(
                    filename: 'blank-'.$row['reference'].'-'.$countMorePhoto.'.jpg',
                    title: 'Blank.pdf',
                    signalement: $signalement,
                    partner: $user?->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
                    user: $user,
                    documentType: DocumentType::AUTRE
                );
                $manager->persist($file);
                ++$countMorePhoto;
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws \Exception
     */
    private function loadNewSignalements(ObjectManager $manager, array $row): void
    {
        $faker = Factory::create('fr_FR');
        $phoneNumber = $row['phone_number'] ?? null;

        /** @var Signalement $signalement */
        $signalement = (new Signalement())
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setCiviliteOccupant($row['civilite_occupant'] ?? 'mme')
            ->setNomOccupant($row['nom_occupant'] ?? $faker->lastName())
            ->setPrenomOccupant($row['prenom_occupant'] ?? $faker->firstName())
            ->setTelOccupant($row['tel_occupant'] ?? $phoneNumber)
            ->setAdresseOccupant($row['adresse_occupant'] ?? str_replace(',', '', $faker->streetAddress()))
            ->setVilleOccupant($row['ville_occupant'])
            ->setCpOccupant($row['cp_occupant'])
            ->setInseeOccupant($row['insee_occupant'])
            ->setNbOccupantsLogement($row['nb_occupants_logement'])
            ->setMailOccupant($row['mail_occupant'] ?? $faker->email())
            ->setEtageOccupant($row['etage_occupant'] ?? $faker->randomNumber(2))
            ->setNumAppartOccupant((string) $faker->randomNumber(3))
            ->setNatureLogement($row['nature_logement'])
            ->setSuperficie($row['superficie'])
            ->setLoyer($row['loyer'] ?? $faker->randomNumber(3))
            ->setDetails($row['details'] ?? '')
            ->setIsProprioAverti($row['is_proprio_averti'] ?? 0)
            ->setNomProprio($row['nom_proprio'] ?? $faker->company())
            ->setMailProprio($faker->companyEmail)
            ->setTelProprio($phoneNumber)
            ->setAdresseProprio($faker->streetAddress())
            ->setCodePostalProprio($faker->postcode())
            ->setVilleProprio($faker->city())
            ->setIsCguAccepted(true)
            ->setIsAllocataire($row['is_allocataire'] ?? null)
            ->setNumAllocataire((string) $faker->randomNumber(6))
            ->setStatut(SignalementStatus::from($row['statut']))
            ->setScore($row['score'] ?? 0)
            ->setScoreBatiment($row['score_batiment'] ?? 0)
            ->setScoreLogement($row['score_logement'] ?? 0)
            ->setReference($row['reference'])
            ->setIsBailEnCours(true)
            ->setIsRelogement($row['is_relogement'] ?? false)
            ->setIsLogementSocial($row['is_logement_social'] ?? false)
            ->setIsPreavisDepart($row['is_preavis_depart'] ?? false)
            ->setIsRefusIntervention(false)
            ->setGeoloc(json_decode($row['geoloc'], true))
            ->setIsRsa(false)
            ->setCodeSuivi($row['code_suivi'] ?? $faker->uuid())
            ->setUuid($row['uuid'])
            ->setValidatedAt(SignalementStatus::ACTIVE->value === $row['statut'] ? new \DateTimeImmutable() : null)
            ->setCreatedAt(
                isset($row['created_at'])
                    ? new \DateTimeImmutable($row['created_at'])
                    : (new \DateTimeImmutable())->modify('-15 days')
            )
            ->setIsUsagerAbandonProcedure($row['usager_abandon_procedure'] ?? null)
            ->setNbPiecesLogement($row['nb_pieces_logement'] ?? 1)
            ->setIsLogementVacant($row['logement_vacant'] ?? false);

        if (isset($row['created_from_uuid'])) {
            $signalement->setCreatedFrom(
                $this->signalementDraftRepository->findOneBy(['uuid' => $row['created_from_uuid']])
            );
        }
        if (isset($row['profile_declarant'])) {
            $signalement->setProfileDeclarant(ProfileDeclarant::from($row['profile_declarant']));
        }
        $signalement
            ->setTypeCompositionLogement(
                TypeCompositionLogementFactory::createFromArray(json_decode($row['type_composition_logement'], true))
            )
            ->setSituationFoyer(
                SituationFoyerFactory::createFromArray(json_decode($row['situation_foyer'], true))
            )
            ->setInformationProcedure(
                InformationProcedureFactory::createFromArray(json_decode($row['information_procedure'], true))
            )
            ->setInformationComplementaire(
                InformationComplementaireFactory::createFromArray(json_decode($row['information_complementaire'], true))
            );

        if (isset($row['is_not_occupant'])) {
            $linkChoices = OccupantLink::getLabelList();
            $signalement
                ->setIsNotOccupant((bool) $row['is_not_occupant'])
                ->setNomDeclarant($row['nom_declarant'] ?? $faker->lastName())
                ->setPrenomDeclarant($row['prenom_declarant'] ?? $faker->firstName())
                ->setTelDeclarant($row['tel_declarant'] ?? $phoneNumber)
                ->setMailDeclarant($row['mail_declarant'] ?? $faker->email())
                ->setStructureDeclarant($faker->company())
                ->setLienDeclarantOccupant($linkChoices[array_rand($linkChoices)]);
        } else {
            $signalement->setIsNotOccupant(false);
        }

        if (isset($row['is_imported'])) {
            $signalement
                ->setIsImported($row['is_imported'])
                ->setModifiedAt(null);
        }

        if (isset($row['date_entree'])) {
            $signalement->setDateEntree(new \DateTimeImmutable($row['date_entree']));
        }

        if (isset($row['bailleur'])) {
            $signalement->setBailleur($this->bailleurRepository->findOneBailleurBy($row['bailleur'], $signalement->getTerritory()));
        }

        if (SignalementStatus::CLOSED->value === $row['statut']) {
            $signalement
                ->setMotifCloture(MotifCloture::tryFrom($row['motif_cloture']))
                ->setClosedAt(new \DateTimeImmutable())
                ->setClosedBy($this->userRepository->findOneBy(['statut' => UserStatus::ACTIVE]));
        }

        if (SignalementStatus::REFUSED->value === $row['statut']) {
            $signalement
                ->setMotifRefus(MotifRefus::tryFrom($row['motif_refus']));
        }

        if (isset($row['created_by'])) {
            $signalement
                ->setCreatedBy($this->userRepository->findOneBy(['email' => $row['created_by']]));
        }

        if (isset($row['type_proprio'])) {
            $signalement
                ->setTypeProprio(ProprioType::tryFrom($row['type_proprio']));
        }

        if (isset($row['desordre_precision'])) {
            foreach ($row['desordre_precision'] as $desordrePrecision) {
                $signalement->addDesordrePrecision(
                    $this->desordrePrecisionRepository->findOneBy(['desordrePrecisionSlug' => $desordrePrecision])
                );
            }
        }

        if (isset($row['qualifications'])) {
            foreach ($row['qualifications'] as $qualificationLabel) {
                $signalementQualification = $this->buildSignalementQualification(
                    $signalement,
                    $row,
                    $qualificationLabel
                );

                $manager->persist($signalementQualification);

                $signalement->addSignalementQualification($signalementQualification);
            }
        }
        if (isset($row['files'])) {
            foreach ($row['files'] as $document) {
                $file = $this->fileFactory->createInstanceFrom(
                    filename: $document['file'],
                    title: $document['titre'],
                    signalement: $signalement,
                    // user: $user,
                    documentType: DocumentType::tryFrom($document['document_type']) ?? DocumentType::PHOTO_SITUATION,
                    desordreSlug: $document['slug'] ?? null
                );
                $manager->persist($file);
            }
        }
        if (!$signalement->getCreatedFrom() && !$signalement->getCreatedBy()) {
            $signalement->setCreatedBy($this->admin);
        }
        $manager->persist($signalement);
        $this->userManager->createUsagerFromSignalement($signalement);
        $this->userManager->createUsagerFromSignalement($signalement, $this->userManager::DECLARANT);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildSignalementQualification(
        Signalement $signalement,
        array $row,
        string $qualificationLabel,
    ): SignalementQualification {
        $faker = Factory::create();
        $signalementQualification = (new SignalementQualification())
            ->setSignalement($signalement)
            ->setQualification(Qualification::from($qualificationLabel))
            ->setCriticites($signalement->getCriticites()->toArray());
        if (Qualification::NON_DECENCE_ENERGETIQUE->name == $qualificationLabel) {
            $qualificationDetails = [];
            $qualificationDetails['consommation_energie'] = $faker->numberBetween(450, 700);
            $qualificationDetails['DPE'] = 1;
            $qualificationDetails['date_dernier_dpe'] = $faker->dateTimeThisYear()->format('Y-m-d');
            $signalementQualification
                ->setStatus(QualificationStatus::NDE_AVEREE)
                ->setDetails($qualificationDetails);
        }

        if (Qualification::RSD->name === $qualificationLabel) {
            $signalementQualification->setStatus(QualificationStatus::RSD_CHECK);
        }

        if (Qualification::NON_DECENCE->name === $qualificationLabel) {
            $signalementQualification->setStatus(QualificationStatus::NON_DECENCE_CHECK);
        }

        return $signalementQualification;
    }

    public function getOrder(): int
    {
        return 13;
    }
}
