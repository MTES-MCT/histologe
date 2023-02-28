<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\MotifCloture;
use App\Entity\Criticite;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\User;
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

    private function loadSignalements(ObjectManager $manager, array $row)
    {
        $faker = Factory::create('fr_FR');
        $phoneNumber = $row['phone_number'];

        $signalement = (new Signalement())
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setNomOccupant($faker->lastName())
            ->setPrenomOccupant($faker->firstName())
            ->setTelOccupant($phoneNumber)
            ->setAdresseOccupant($row['adresse_occupant'] ?? $faker->streetAddress())
            ->setVilleOccupant($row['ville_occupant'])
            ->setCpOccupant($row['cp_occupant'])
            ->setInseeOccupant($row['insee_occupant'])
            ->setNbOccupantsLogement($row['nb_occupants_logement'])
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
            ->setScoreCreation($row['score_creation'])
            ->setReference($row['reference'])
            ->setIsBailEnCours(false)
            ->setIsRelogement(false)
            ->setIsLogementSocial(false)
            ->setIsPreavisDepart(false)
            ->setDocuments([])
            ->setPhotos([])
            ->setGeoloc(json_decode($row['geoloc'], true))
            ->setIsRsa(false)
            ->setCodeSuivi($faker->uuid())
            ->setUuid($row['uuid'])
            ->setSituationOccupant($row['situation_occupant'])
            ->setValidatedAt(Signalement::STATUS_ACTIVE === $row['statut'] ? new \DateTimeImmutable() : null)
            ->setOrigineSignalement($row['origine_signalement'])
            ->setCreatedAt((new \DateTimeImmutable())->modify('-15 days'));

        if (isset($row['is_not_occupant'])) {
            $signalement
                ->setIsNotOccupant($row['is_not_occupant'])
                ->setNomDeclarant($faker->lastName())
                ->setPrenomDeclarant($faker->firstName())
                ->setTelDeclarant($phoneNumber)
                ->setMailDeclarant($faker->email())
                ->setStructureDeclarant($faker->company())
                ->setLienDeclarantOccupant(SignalementType::LINK_CHOICES[array_rand(SignalementType::LINK_CHOICES)]);
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
    }

    public function getOrder(): int
    {
        return 8;
    }
}
