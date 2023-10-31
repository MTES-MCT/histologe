<?php

namespace App\DataFixtures\Loader;

use App\Entity\Criticite;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Form\SignalementType;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use App\Repository\SituationRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Yaml\Yaml;

class LoadSignalementData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private TerritoryRepository $territoryRepository,
        private SituationRepository $situationRepository,
        private CritereRepository $critereRepository,
        private CriticiteRepository $criticiteRepository,
        private TagRepository $tagRepository,
        private UserRepository $userRepository,
        private readonly FileFactory $fileFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $signalementRows = Yaml::parseFile(__DIR__.'/../Files/Signalement.yml');
        foreach ($signalementRows['signalements'] as $row) {
            $this->loadSignalements($manager, $row);
        }

        $manager->flush();
    }

    /**
     * @throws \Exception
     */
    private function loadSignalements(ObjectManager $manager, array $row)
    {
        $faker = Factory::create('fr_FR');
        $phoneNumber = $row['phone_number'];

        $signalement = (new Signalement())
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setNomOccupant($faker->lastName())
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
            ->setMailOccupant($faker->email())
            ->setEtageOccupant($row['etage_occupant'])
            ->setNumAppartOccupant($faker->randomNumber(3))
            ->setNatureLogement($row['nature_logement'])
            ->setTypeLogement($row['type_logement'])
            ->setSuperficie($row['superficie'])
            ->setLoyer($row['loyer'])
            ->setDetails($row['details'])
            ->setIsProprioAverti($row['is_proprio_averti'])
            ->setModeContactProprio(json_decode($row['mode_contact_proprio'], true))
            ->setNomProprio($faker->company())
            ->setMailProprio($faker->companyEmail)
            ->setTelProprio($phoneNumber)
            ->setAdresseProprio($faker->address())
            ->setIsCguAccepted(true)
            ->setIsAllocataire($row['is_allocataire'])
            ->setNumAllocataire($faker->randomNumber(6))
            ->setStatut($row['statut'])
            ->setScore($row['score'])
            ->setReference($row['reference'])
            ->setIsBailEnCours(false)
            ->setIsRelogement(false)
            ->setIsLogementSocial($row['is_logement_social'])
            ->setIsPreavisDepart(false)
            ->setIsRefusIntervention(false)
            ->setGeoloc(json_decode($row['geoloc'], true))
            ->setIsRsa(false)
            ->setCodeSuivi($row['code_suivi'] ?? $faker->uuid())
            ->setUuid($row['uuid'])
            ->setSituationOccupant($row['situation_occupant'])
            ->setValidatedAt(Signalement::STATUS_ACTIVE === $row['statut'] ? new \DateTimeImmutable() : null)
            ->setOrigineSignalement($row['origine_signalement'])
            ->setCreatedAt(
                isset($row['created_at'])
                    ? new \DateTimeImmutable($row['created_at'])
                    : (new \DateTimeImmutable())->modify('-15 days')
            )
            ->setIsUsagerAbandonProcedure(0);
        if (isset($row['is_not_occupant'])) {
            $signalement
                ->setIsNotOccupant($row['is_not_occupant'])
                ->setNomDeclarant($faker->lastName())
                ->setPrenomDeclarant($faker->firstName())
                ->setTelDeclarant($phoneNumber)
                ->setMailDeclarant($faker->email())
                ->setStructureDeclarant($faker->company())
                ->setLienDeclarantOccupant(SignalementType::LINK_CHOICES[array_rand(SignalementType::LINK_CHOICES)]);
        } else {
            $signalement->setIsNotOccupant(0);
        }

        if (isset($row['is_imported'])) {
            $signalement
                ->setIsImported($row['is_imported'])
                ->setModifiedAt(null);
        }

        if (isset($row['date_entree'])) {
            $signalement->setDateEntree(new \DateTimeImmutable($row['date_entree']));
        }

        if (Signalement::STATUS_CLOSED === $row['statut']) {
            $signalement
                ->setMotifCloture(MotifCloture::tryFrom($row['motif_cloture']))
                ->setClosedAt(new \DateTimeImmutable())
                ->setClosedBy($this->userRepository->findOneBy(['statut' => User::STATUS_ACTIVE]));
        }

        foreach ($row['tags'] as $tag) {
            $signalement->addTag($this->tagRepository->findOneBy(['label' => $tag]));
        }

        foreach ($row['situations'] as $situation) {
            $signalement->addSituation($this->situationRepository->findOneBy(['label' => $situation]));
        }

        foreach ($row['criteres'] as $critere) {
            $signalement->addCritere($this->critereRepository->findOneBy(['label' => $critere]));
        }

        foreach ($row['criticites'] as $criticite) {
            $signalement->addCriticite($this->criticiteRepository->findOneBy(['label' => $criticite]));
        }

        $manager->persist($signalement);

        if (isset($row['qualifications'])) {
            foreach ($row['qualifications'] as $qualificationLabel) {
                $signalementQualification = (new SignalementQualification())
                ->setSignalement($signalement)
                ->setQualification(Qualification::from($qualificationLabel))
                ->setDernierBailAt(
                    isset($row['date_entree'])
                        ? new \DateTimeImmutable($row['date_entree'])
                        : new \DateTimeImmutable()
                )
                ->setCriticites($signalement->getCriticites()->map(function (Criticite $criticite) {
                    return $criticite->getId();
                })->toArray());
                if (Qualification::NON_DECENCE_ENERGETIQUE->name == $qualificationLabel) {
                    $qualificationDetails = [];
                    $qualificationDetails['consommation_energie'] = $faker->numberBetween(450, 700);
                    $qualificationDetails['DPE'] = 1;
                    $qualificationDetails['date_dernier_dpe'] = $faker->dateTimeThisYear()->format('Y-m-d');
                    $signalementQualification
                    ->setStatus(QualificationStatus::NDE_AVEREE)
                    ->setDetails($qualificationDetails);
                }

                $manager->persist($signalementQualification);

                $signalement->addSignalementQualification($signalementQualification);
                $manager->persist($signalement);
            }
        }

        $documentsAndPhotos = [
            [
                'file' => 'test1.23.pdf',
                'titre' => 'Fiche reperage.pdf',
                'user' => 1,
            ],
            [
                'file' => 'test1.pdf',
                'titre' => 'Compte rendu de visite.pdf',
                'user' => 22,
            ],
            [
                'file' => 'blank-'.$row['reference'].'.pdf',
                'titre' => 'Blank.pdf',
                'user' => 1,
            ],
            [
                'file' => 'Capture-d-ecran-du-2023-06-13-12-58-43-648b2a6b9730f.png',
                'titre' => '20220520_112424.jpg',
                'user' => 1,
            ],
            [
                'file' => 'Capture-d-ecran-du-2023-04-07-15-27-36-64302a1b57a20.png',
                'titre' => 'IMG_20230220_141432735_HDR.jpg',
                'user' => 23,
            ],
            [
                'file' => 'blank-'.$row['reference'].'.jpg',
                'titre' => 'Blank.pdf',
                'user' => 1,
            ],
        ];

        foreach ($documentsAndPhotos as $document) {
            $user = $this->userRepository->findOneBy(['id' => $document['user']]);
            $file = $this->fileFactory->createInstanceFrom(
                filename: $document['file'],
                title: $document['titre'],
                type: 'pdf' === pathinfo($document['file'], \PATHINFO_EXTENSION)
                    ? File::FILE_TYPE_DOCUMENT
                    : File::FILE_TYPE_PHOTO,
                signalement: $signalement,
                user: $user
            );
            $manager->persist($file);
        }

        if ('2022-4' === $row['reference']) {
            $countMorePhoto = 1;
            $user = $this->userRepository->findOneBy(['id' => 1]);
            while ($countMorePhoto < 12) {
                $file = $this->fileFactory->createInstanceFrom(
                    filename: 'blank-'.$row['reference'].'-'.$countMorePhoto.'.jpg',
                    title: 'Blank.pdf',
                    type: File::FILE_TYPE_PHOTO,
                    signalement: $signalement,
                    user: $user
                );
                $manager->persist($file);
                ++$countMorePhoto;
            }
        }
    }

    public function getOrder(): int
    {
        return 8;
    }
}
