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
            'geoloc' => ['lat' => 5.386161, 'lng' => 43.312827],
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
        $this->assertEquals($data['structureDeclarant'], $signalement->getStructureDeclarant());
        $this->assertEquals($data['lienDeclarantOccupant'], $signalement->getLienDeclarantOccupant());
        $this->assertEquals($data['prenomOccupant'], $signalement->getPrenomOccupant());
        $this->assertEquals($data['nomOccupant'], $signalement->getNomOccupant());
        $this->assertEquals($data['telOccupant'], $signalement->getTelOccupant());
        $this->assertEquals($data['mailOccupant'], $signalement->getMailOccupant());
        $this->assertEquals($data['adresseOccupant'], $signalement->getAdresseOccupant());
        $this->assertEquals($data['cpOccupant'], $signalement->getCpOccupant());
        $this->assertEquals($data['villeOccupant'], $signalement->getVilleOccupant());
        $this->assertEquals($data['inseeOccupant'], $signalement->getInseeOccupant());
        $this->assertEquals($data['etageOccupant'], $signalement->getEtageOccupant());
        $this->assertEquals($data['escalierOccupant'], $signalement->getEscalierOccupant());
        $this->assertEquals($data['numAppartOccupant'], $signalement->getNumAppartOccupant());
        $this->assertEquals($data['adresseOccupant'], $signalement->getAdresseOccupant());
        $this->assertEquals($data['naissanceOccupants'], $signalement->getNaissanceOccupants());
        $this->assertEquals($data['situationProOccupant'], $signalement->getSituationProOccupant());
        $this->assertEquals($data['situationOccupant'], $signalement->getSituationOccupant());
        $this->assertEquals($data['adresseAutreOccupant'], $signalement->getAdresseAutreOccupant());

        $this->assertEquals($data['nomProprio'], $signalement->getNomProprio());
        $this->assertEquals($data['telProprio'], $signalement->getTelProprio());
        $this->assertEquals($data['mailProprio'], $signalement->getMailProprio());
        $this->assertEquals($data['adresseProprio'], $signalement->getAdresseProprio());
        $this->assertEquals($data['modeContactProprio'], $signalement->getModeContactProprio());
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
        $this->assertEquals($data['nbChambresLogement'], $signalement->getNbChambresLogement());
        $this->assertEquals($data['nbNiveauxLogement'], $signalement->getNbNiveauxLogement());

        $this->assertEquals($data['loyer'], $signalement->getLoyer());
        $this->assertEquals(mb_strtoupper($data['typeLogement']), $signalement->getTypeLogement());
        $this->assertEquals(mb_strtolower($data['natureLogement']), $signalement->getNatureLogement());
        $this->assertEquals($data['superficie'], $signalement->getSuperficie());

        $this->assertEquals($data['isAllocataire'], $signalement->getIsAllocataire());
        $this->assertEquals($data['numAllocataire'], $signalement->getNumAllocataire());
        $this->assertEquals($data['montantAllocation'], $signalement->getMontantAllocation());

        $this->assertEquals($data['isLogementSocial'], $signalement->getIsLogementSocial());
        $this->assertEquals($data['isRelogement'], $signalement->getIsRelogement());

        $this->assertEquals($data['isLogementCollectif'], $signalement->getIsLogementCollectif());
        $this->assertEquals($data['isPreavisDepart'], $signalement->getIsPreavisDepart());
        $this->assertEquals($data['isNotOccupant'], $signalement->getIsNotOccupant());
        $this->assertEquals($data['isOccupantPresentVisite'], $signalement->getIsOccupantPresentVisite());
        $this->assertEquals($data['isRsa'], $signalement->getIsRsa());
        $this->assertEquals($data['isConstructionAvant1949'], $signalement->getIsConstructionAvant1949());
        $this->assertEquals($data['isFondSolidariteLogement'], $signalement->getIsFondSolidariteLogement());
        $this->assertEquals($data['isRisqueSurOccupation'], $signalement->getIsRisqueSurOccupation());
        $this->assertEquals($data['isBailEnCours'], $signalement->getIsBailEnCours());
        $this->assertEquals($data['isRefusIntervention'], $signalement->getIsRefusIntervention());
        $this->assertEquals($data['isCguAccepted'], $signalement->getIsCguAccepted());
        $this->assertEquals($data['isConsentementTiers'], $signalement->getIsConsentementTiers());

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
        $this->assertEquals($data['dateVisite'], $signalement->getDateVisite());
        $this->assertEquals($data['origineSignalement'], $signalement->getOrigineSignalement());
        $this->assertEquals($data['typeEnergieLogement'], $signalement->getTypeEnergieLogement());
        $this->assertEquals($data['anneeConstruction'], $signalement->getAnneeConstruction());
        $this->assertEquals($data['codeProcedure'], $signalement->getCodeProcedure());
        $this->assertEquals($data['raisonRefusIntervention'], $signalement->getRaisonRefusIntervention());

        $this->assertEquals($data['nomReferentSocial'], $signalement->getNomReferentSocial());
        $this->assertEquals($data['StructureReferentSocial'], $signalement->getStructureReferentSocial());
        $this->assertEquals([$data['mailOccupant'], $data['mailDeclarant']], $signalement->getMailUsagers());
        $this->assertTrue($signalement->getIsImported());

        $this->assertEmpty($signalement->getPhotos());
        $this->assertEmpty($signalement->getDocuments());
        $this->assertEmpty($signalement->getTelOccupantBis());
        $this->assertEmpty($signalement->getIsDiagSocioTechnique());
        $this->assertEmpty($signalement->getScoreCloture());
        $this->assertEmpty($signalement->getJsonContent());
        $this->assertEmpty($signalement->getModifiedBy());
        $this->assertEmpty($signalement->getAffectationStatusByPartner());

        $this->assertCount(76, $data, 'Array $data should have 76 keys');
    }
}
