<?php

namespace App\Tests;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Entity\User;
use Faker\Factory;
use Faker\Provider\Address;

trait FixturesHelper
{
    public function getAffectation(PartnerType $partnerType): Affectation
    {
        $faker = Factory::create();

        return (new Affectation())
            ->setPartner(
                (new Partner())
                    ->setEsaboraToken($faker->password(20))
                    ->setEsaboraUrl($faker->url())
                    ->setType($partnerType)
            )->setSignalement(
                (new Signalement())
                    ->setUuid($faker->uuid())
            );
    }

    public function getSignalementAffectation(PartnerType $partnerType): Affectation
    {
        $faker = Factory::create('fr_FR');
        $file = __DIR__.'/../../tests/files/sample.png';

        $criticite = (new Criticite())
            ->setCritere(
                (new Critere())
                    ->setLabel('critere')
                    ->setDescription('description critere')
                    ->setSituation(
                        (new Situation())
                            ->setLabel('situation')
                            ->setMenuLabel('menu-situation')
                    ))
            ->setLabel('criticite')
            ->setScore(2);

        $signalement = (new Signalement())
            ->addCriticite($criticite)
            ->setIsProprioAverti(false)
            ->setNbAdultes(2)
            ->setNbEnfantsP6(1)
            ->setNbEnfantsM6(1)
            ->setTelOccupant($faker->phoneNumber())
            ->setAdresseOccupant('25 rue du test')
            ->setEtageOccupant(2)
            ->setVilleOccupant($faker->city())
            ->setCpOccupant(Address::postcode())
            ->setNumAppartOccupant(2)
            ->setNomOccupant($faker->lastName())
            ->setPrenomOccupant($faker->firstName())
            ->setDocuments([
                [
                    'file' => $file,
                    'titre' => 'Doc',
                    'date' => '02.12.2022', ],
            ])
            ->setPhotos([
                [
                    'file' => $file,
                    'titre' => 'Photo',
                    'date' => '02.12.2022',
                ],
            ])
            ->addSuivi($this->getSuiviPartner());

        $partner = (new Partner())
            ->setNom($faker->company())
            ->setEsaboraUrl($faker->url())
            ->setEsaboraToken($faker->password(20))
            ->setType($partnerType);

        return (new Affectation())->setSignalement($signalement)->setPartner($partner);
    }

    public function getSuiviPartner(): Suivi
    {
        return (new Suivi())
            ->setType(Suivi::TYPE_PARTNER)
            ->setDescription('Problèmes de condensation et de moisissures')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setCreatedBy(new User());
    }

    public function getAdditionalInformationArrete(): array
    {
        return [
            'arrete_numero' => '2023/DD13/00664',
            'arrete_type' => 'Arrêté L.511-11 - Suroccupation',
            'arrete_mainlevee_date' => '01/08/2023',
            'arrete_mainlevee_numero' => '2023-DD13-00173',
        ];
    }
}
