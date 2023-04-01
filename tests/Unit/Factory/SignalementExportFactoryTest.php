<?php

namespace App\Tests\Unit\Factory;

use App\Dto\SignalementExport;
use App\Entity\Enum\MotifCloture;
use App\Entity\User;
use App\Factory\SignalementExportFactory;
use App\Tests\UserHelper;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class SignalementExportFactoryTest extends TestCase
{
    use UserHelper;

    public function testCreateSignalementExportFactory(): void
    {
        $faker = Factory::create();

        $data = [
            'id' => 21081,
            'uuid' => '661c23e9-8abb-4840-ab9d-4880f475ccfb',
            'reference' => '2023-542',
            'createdAt' => new \DateTimeImmutable(),
            'statut' => 2,
            'scoreCreation' => 100.0,
            'newScoreCreation' => 0.0,
            'isNotOccupant' => false,
            'nomOccupant' => $faker->lastName(),
            'prenomOccupant' => $faker->firstName(),
            'adresseOccupant' => $faker->streetAddress(),
            'villeOccupant' => $faker->city(),
            'rawAffectations' => 'DDETS-PS-DL PE - Mission Logement indigne||0;M.A.M.P. - CT1 / EAH Marseille||0;MARSEILLE||1',
            'affectationPartnerName' => 'DDETS-PS-DL PE - Mission Logement indigne;M.A.M.P. - CT1 / EAH Marseille;MARSEILLE',
            'affectationStatus' => '0;1',
            'affectationPartnerId' => '636;645;708',
            'details' => $faker->text(),
            'telOccupant' => $faker->phoneNumber(),
            'telOccupantBis' => null,
            'mailOccupant' => $faker->email(),
            'cpOccupant' => $faker->postcode(),
            'inseeOccupant' => $faker->postcode(),
            'etageOccupant' => null,
            'escalierOccupant' => null,
            'numAppartOccupant' => null,
            'adresseAutreOccupant' => null,
            'photos' => [],
            'documents' => [],
            'isProprioAverti' => true,
            'nbAdultes' => '2',
            'nbEnfantsM6' => null,
            'nbEnfantsP6' => null,
            'isAllocataire' => 'CAF',
            'numAllocataire' => null,
            'natureLogement' => null,
            'superficie' => null,
            'nomProprio' => $faker->company(),
            'isLogementSocial' => null,
            'isPreavisDepart' => false,
            'isRelogement' => null,
            'nomDeclarant' => null,
            'structureDeclarant' => null,
            'lienDeclarantOccupant' => null,
            'dateVisite' => new \DateTimeImmutable(),
            'isOccupantPresentVisite' => false,
            'modifiedAt' => new \DateTimeImmutable(),
            'closedAt' => new \DateTimeImmutable(),
            'motifCloture' => MotifCloture::INSALUBRITE,
            'scoreCloture' => null,
            'familleSituation' => "l'état et propreté du logement|l'état et propreté du logement|",
            'desordres' => "Les sols sont humides.|Les installations électriques ne sont pas en bon état.|Les murs ont des fissures.|De l'eau s’infiltre dans mon logement.|Il y a des trace ",
            'etiquettes' => null,
        ];

        $user = $this->getUserFromRole(User::ROLE_ADMIN);

        $signalementExportFactory = (new SignalementExportFactory())->createInstanceFrom($user, $data);
        $this->assertInstanceOf(SignalementExport::class, $signalementExportFactory);
        $this->assertEquals('en cours', $signalementExportFactory->statut);
        $this->assertEquals(MotifCloture::INSALUBRITE->label(), $signalementExportFactory->motifCloture);

        $this->assertEquals((new \DateTimeImmutable())->format('d/m/y'), $signalementExportFactory->createdAt);
        $this->assertEquals((new \DateTimeImmutable())->format('d/m/y'), $signalementExportFactory->modifiedAt);
        $this->assertEquals((new \DateTimeImmutable())->format('d/m/y'), $signalementExportFactory->closedAt);
        $this->assertEquals((new \DateTimeImmutable())->format('d/m/y'), $signalementExportFactory->dateVisite);

        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->telephoneOccupantBis);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->etageOccupant);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->escalierOccupant);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->numAppartOccupant);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->adresseAutreOccupant);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->nbEnfantsM6);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->nbEnfantsP6);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->numAllocataire);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->natureLogement);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->superficie);

        $this->assertEquals(SignalementExportFactory::NON, $signalementExportFactory->photos);
        $this->assertEquals(SignalementExportFactory::NON, $signalementExportFactory->documents);
        $this->assertEquals(SignalementExportFactory::OUI, $signalementExportFactory->isProprioAverti);
        $this->assertEquals(SignalementExportFactory::OUI, $signalementExportFactory->isProprioAverti);
        $this->assertEquals(SignalementExportFactory::OUI, $signalementExportFactory->isAllocataire);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->isLogementSocial);
        $this->assertEquals(SignalementExportFactory::NON, $signalementExportFactory->isPreavisDepart);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->isRelogement);
        $this->assertEquals(SignalementExportFactory::NON, $signalementExportFactory->isNotOccupant);
        $this->assertEquals(SignalementExportFactory::NON, $signalementExportFactory->isOccupantPresentVisite);
    }
}
