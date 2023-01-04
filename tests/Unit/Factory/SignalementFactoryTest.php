<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Factory\SignalementFactory;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementFactoryTest extends KernelTestCase
{
    public function testCreateSignalementIsValid()
    {
        $faker = Factory::create();
        $data = [
            'reference' => (new \DateTimeImmutable())->format('Y').'-1',
            'createdAt' => new \DateTimeImmutable(),
            'closedAt' => new \DateTimeImmutable(),
            'motifCloture' => null,
            'photos' => null,
            'documents' => null,
            'details' => $faker->realText(),
            'isProprioAverti' => false,
            'prorioAvertiAt' => new \DateTimeImmutable(),
            'nbAdultes' => $faker->randomDigit(),
            'nbEnfantsM6' => $faker->randomDigit(),
            'nbEnfantsP6' => $faker->randomDigit(),
            'nbOccupantsLogement' => $faker->randomDigit(),
            'isAllocataire' => false,
            'numAllocataire' => null,
            'typeLogement' => 'maison',
            'superficie' => $faker->numberBetween(30, 100),
            'nomProprio' => $faker->lastName(),
            'adresseProprio' => $faker->streetAddress(),
            'telProprio' => $faker->phoneNumber(),
            'mailProprio' => $faker->email(),
            'isLogementSocial' => true,
            'isPreavisDepart' => false,
            'isRelogement' => false,
            'isNotOccupant' => false,
            'nomDeclarant' => $faker->lastName(),
            'prenomDeclarant' => $faker->firstName(),
            'telDeclarant' => $faker->phoneNumber(),
            'mailDeclarant' => $faker->email(),
            'lienDeclarantOccupant' => 'PROCHE',
            'structureDeclarant' => null,
            'nomOccupant' => $faker->firstName(),
            'prenomOccupant' => $faker->firstName(),
            'telOccupant' => $faker->phoneNumber(),
            'mailOccupant' => $faker->email(),
            'adresseOccupant' => $faker->address(),
            'cpOccupant' => $faker->postcode(),
            'villeOccupant' => $faker->city(),
            'inseeOccupant' => $faker->postcode(),
            'dateVisite' => new \DateTimeImmutable(),
            'isOccupantPresentVisite' => true,
            'isSituationHandicap' => false,
            'etageOccupant' => $faker->randomDigit(),
            'escalierOccupant' => $faker->randomDigit(),
            'numAppartOccupant' => $faker->randomDigit(),
            'modeContactProprio' => ['sms'],
            'isRsa' => false,
            'isConstructionAvant1949' => false,
            'isFondSolidariteLogement' => false,
            'isRisqueSurOccupation' => false,
            'numeroInvariant' => null,
            'natureLogement' => 'maison',
            'loyer' => $faker->numberBetween(300, 1000),
            'isBailEnCours' => true,
            'dateEntree' => new \DateTimeImmutable(),
            'isRefusIntervention' => false,
            'raisonRefusIntervention' => null,
            'isCguAccepted' => true,
            'modifiedAt' => null,
            'statut' => Signalement::STATUS_ACTIVE,
            'geoloc' => null,
            'montantAllocation' => null,
            'codeProcedure' => null,
            'adresseAutreOccupant' => null,
            'isConsentementTiers' => true,
            'anneeConstruction' => '1995',
            'typeEnergieLogement' => null,
            'origineSignalement' => null,
            'situationOccupant' => null,
            'situationProOccupant' => null,
            'naissanceOccupants' => null,
            'isLogementCollectif' => false,
            'nomReferentSocial' => null,
            'StructureReferentSocial' => null,
            'mailSyndic' => $faker->companyEmail(),
            'telSyndic' => $faker->phoneNumber(),
            'nomSyndic' => $faker->company(),
            'nomSci' => $faker->company(),
            'nomRepresentantSci' => $faker->lastName().' '.$faker->firstName,
            'telSci' => $faker->phoneNumber(),
            'mailSci' => $faker->companyEmail(),
            'nbPiecesLogement' => $faker->randomDigit(),
            'nbChambresLogement' => $faker->randomDigit(),
            'nbNiveauxLogement' => $faker->randomDigit(),
            ];

        $territory = (new Territory())
            ->setName('Ain')
            ->setZip('01')
            ->setIsActive(true);

        $signalement = (new SignalementFactory())->createInstanceFrom($territory, $data, true);

        $this->assertEquals($data['reference'], $signalement->getReference());
        $this->assertEquals($data['nomDeclarant'], $signalement->getNomDeclarant());
        $this->assertEquals($data['prenomDeclarant'], $signalement->getPrenomDeclarant());
        $this->assertEquals($data['telDeclarant'], $signalement->getTelDeclarant());
        $this->assertEquals($data['mailDeclarant'], $signalement->getMailDeclarant());
        $this->assertEquals($data['lienDeclarantOccupant'], $signalement->getLienDeclarantOccupant());
        $this->assertEquals($data['structureDeclarant'], $signalement->getStructureDeclarant());
        $this->assertEquals($data['prenomOccupant'], $signalement->getPrenomOccupant());
        $this->assertEquals($data['nomOccupant'], $signalement->getNomOccupant());
        $this->assertEquals($data['telOccupant'], $signalement->getTelOccupant());
        $this->assertEquals($data['mailOccupant'], $signalement->getMailOccupant());
        $this->assertEquals($data['adresseOccupant'], $signalement->getAdresseOccupant());
        $this->assertEquals($data['cpOccupant'], $signalement->getCpOccupant());
        $this->assertEquals($data['villeOccupant'], $signalement->getVilleOccupant());
        $this->assertEquals($data['etageOccupant'], $signalement->getEtageOccupant());
        $this->assertEquals($data['numAppartOccupant'], $signalement->getNumAppartOccupant());
        $this->assertEquals($data['nomProprio'], $signalement->getNomProprio());
        $this->assertEquals($data['adresseOccupant'], $signalement->getAdresseOccupant());
        $this->assertEquals($data['telProprio'], $signalement->getTelProprio());
        $this->assertEquals($data['mailProprio'], $signalement->getMailProprio());
        $this->assertEquals($data['details'], $signalement->getDetails());

        $this->assertEquals($data['isProprioAverti'], $signalement->getIsProprioAverti());
        $this->assertEquals($data['nbAdultes'], $signalement->getNbAdultes());
        $this->assertEquals($data['nbEnfantsM6'], $signalement->getNbEnfantsM6());
        $this->assertEquals($data['nbEnfantsP6'], $signalement->getNbEnfantsP6());
        $this->assertEquals($data['natureLogement'], $signalement->getNatureLogement());
        $this->assertEquals($data['superficie'], $signalement->getSuperficie());
        $this->assertEquals($data['isAllocataire'], $signalement->getIsAllocataire());
        $this->assertEquals($data['isSituationHandicap'], $signalement->getIsSituationHandicap());
        $this->assertEquals($data['isLogementSocial'], $signalement->getIsLogementSocial());
        $this->assertEquals($data['isRelogement'], $signalement->getIsRelogement());

        $this->assertEquals($data['nomSci'], $signalement->getNomSci());
        $this->assertEquals($data['nomSci'], $signalement->getNomSci());

    }
}
