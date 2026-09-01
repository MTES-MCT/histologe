<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Territory;
use App\Factory\SignalementImportFactory;
use App\Service\Signalement\SignalementAddressUpdater;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementFactoryTest extends KernelTestCase
{
    public function testCreateSignalementIsValid(): void
    {
        $faker = Factory::create('fr_FR');
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
            'isAllocataire' => true,
            'numAllocataire' => $faker->randomNumber(7),
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
            'adresseOccupant' => '22 Rue du test',
            'cpOccupant' => '01170',
            'villeOccupant' => 'Gex',
            'inseeOccupant' => '01173',
            'etageOccupant' => $faker->randomDigit(),
            'escalierOccupant' => $faker->randomDigit(),
            'numAppartOccupant' => $faker->randomDigit(),
            'isRsa' => false,
            'isConstructionAvant1949' => false,
            'numeroInvariant' => null,
            'natureLogement' => 'maison',
            'loyer' => $faker->numberBetween(300, 1000),
            'isBailEnCours' => true,
            'dateEntree' => new \DateTimeImmutable(),
            'isCguAccepted' => true,
            'modifiedAt' => null,
            'statut' => SignalementStatus::ACTIVE,
            'geoloc' => ['lat' => 43.312827, 'lng' => 5.386161],
            'montantAllocation' => null,
            'adresseAutreOccupant' => null,
            'anneeConstruction' => '1995',
            'naissanceOccupants' => null,
            'nbPiecesLogement' => $faker->randomDigit(),
            'nbNiveauxLogement' => $faker->randomDigit(),
        ];

        $territory = (new Territory())
            ->setName('Ain')
            ->setZip('01')
            ->setIsActive(true);
        $reflection = new \ReflectionClass($territory);
        $property = $reflection->getProperty('id');
        $property->setValue($territory, 1);

        $signalementImportFactory = new SignalementImportFactory($this->getContainer()->get(SignalementAddressUpdater::class));
        $signalement = $signalementImportFactory->create($territory, $data);

        $this->assertEquals($data['reference'], $signalement->getReference());
        $this->assertEquals($data['nomDeclarant'], $signalement->getNomDeclarant());
        $this->assertEquals($data['prenomDeclarant'], $signalement->getPrenomDeclarant());
        $this->assertEquals($data['telDeclarant'], $signalement->getTelDeclarant());
        $this->assertEquals($data['mailDeclarant'], $signalement->getMailDeclarant());
        $this->assertEquals($data['structureDeclarant'], $signalement->getStructureDeclarant());
        $this->assertEquals($data['lienDeclarantOccupant'], $signalement->getLienDeclarantOccupant());
        $this->assertEquals($data['prenomOccupant'], $signalement->getPrenomOccupant());
        $this->assertEquals($data['nomOccupant'], $signalement->getNomOccupant());
        $this->assertEquals($data['telOccupant'], $signalement->getTelOccupant());
        $this->assertEquals($data['mailOccupant'], $signalement->getMailOccupant());
        $this->assertEquals($data['adresseOccupant'], $signalement->getAddress()->getHousenumberAndStreet());
        $this->assertEquals($data['cpOccupant'], $signalement->getAddress()->getPostCode());
        $this->assertEquals($data['villeOccupant'], $signalement->getAddress()->getCity());
        $this->assertEquals($data['inseeOccupant'], $signalement->getAddress()->getCityCode());
        $this->assertEquals($data['etageOccupant'], $signalement->getEtageOccupant());
        $this->assertEquals($data['escalierOccupant'], $signalement->getEscalierOccupant());
        $this->assertEquals($data['numAppartOccupant'], $signalement->getNumAppartOccupant());
        $this->assertEquals($data['naissanceOccupants'], $signalement->getNaissanceOccupants());
        $this->assertEquals($data['adresseAutreOccupant'], $signalement->getAdresseAutreOccupant());

        $this->assertEquals($data['nomProprio'], $signalement->getNomProprio());
        $this->assertEquals($data['telProprio'], $signalement->getTelProprio());
        $this->assertEquals($data['mailProprio'], $signalement->getMailProprio());
        $this->assertEquals($data['adresseProprio'], $signalement->getAdresseProprio());
        $this->assertEquals($data['details'], $signalement->getDetails());
        $this->assertEquals($data['statut'], $signalement->getStatut());
        $this->assertEquals($data['modifiedAt'], $signalement->getModifiedAt());

        $this->assertEquals($data['isProprioAverti'], $signalement->getIsProprioAverti());
        $this->assertEquals($data['prorioAvertiAt'], $signalement->getProprioAvertiAt());

        $this->assertEquals($data['nbAdultes'], $signalement->getNbAdultes());
        $this->assertEquals($data['nbEnfantsM6'], $signalement->getNbEnfantsM6());
        $this->assertEquals($data['nbEnfantsP6'], $signalement->getNbEnfantsP6());
        $this->assertEquals($data['nbOccupantsLogement'], $signalement->getNbOccupantsLogement());

        $this->assertEquals($data['nbPiecesLogement'], $signalement->getNbPiecesLogement());
        $this->assertEquals($data['nbNiveauxLogement'], $signalement->getNbNiveauxLogement());

        $this->assertEquals($data['loyer'], $signalement->getLoyer());
        $this->assertEquals(mb_strtolower($data['natureLogement']), $signalement->getNatureLogement());
        $this->assertEquals($data['superficie'], $signalement->getSuperficie());

        $this->assertEquals($data['isAllocataire'], $signalement->getIsAllocataire());
        $this->assertEquals($data['numAllocataire'], $signalement->getNumAllocataire());
        $this->assertEquals($data['montantAllocation'], $signalement->getMontantAllocation());

        $this->assertEquals($data['isLogementSocial'], $signalement->getIsLogementSocial());
        $this->assertEquals($data['isRelogement'], $signalement->getIsRelogement());

        $this->assertEquals($data['isPreavisDepart'], $signalement->getIsPreavisDepart());
        $this->assertEquals($data['isNotOccupant'], $signalement->getIsNotOccupant());
        $this->assertEquals($data['isRsa'], $signalement->getIsRsa());
        $this->assertEquals($data['isConstructionAvant1949'], $signalement->getIsConstructionAvant1949());
        $this->assertEquals($data['isBailEnCours'], $signalement->getIsBailEnCours());
        $this->assertEquals($data['isCguAccepted'], $signalement->getIsCguAccepted());

        $this->assertEquals($data['createdAt'], $signalement->getCreatedAt());
        $this->assertEquals(
            $signalement->getValidatedAt()->getTimestamp(),
            $signalement->getCreatedAt()->getTimestamp()
        )
        ;
        $this->assertEquals($data['motifCloture'], $signalement->getMotifCloture()?->label());
        $this->assertEquals($data['closedAt'], $signalement->getClosedAt());
        $this->assertEquals($data['numeroInvariant'], $signalement->getNumeroInvariant());
        $this->assertEquals($data['dateEntree'], $signalement->getDateEntree());
        // $this->assertEquals($data['dateVisite'], $signalement->getDateVisite()); TODO : dateVisite
        $this->assertEquals($data['anneeConstruction'], $signalement->getAnneeConstruction());

        $this->assertEquals([$data['mailOccupant'], $data['mailDeclarant']], $signalement->getMailUsagers());
        $this->assertTrue($signalement->getIsImported());

        $this->assertEmpty($signalement->getFiles());
        $this->assertEmpty($signalement->getTelOccupantBis());
        $this->assertEmpty($signalement->getJsonContent());
        $this->assertEmpty($signalement->getModifiedBy());
        $this->assertEmpty($signalement->getAffectationStatusByPartner());

        $this->assertCount(58, $data, 'Array $data should have 58 keys');
    }
}
