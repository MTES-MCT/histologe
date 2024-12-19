<?php

namespace App\Tests\Unit\Factory;

use App\Dto\SignalementExport;
use App\Entity\Enum\DebutDesordres;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\VisiteStatus;
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
            'score' => 100.0,
            'isNotOccupant' => false,
            'profileDeclarant' => ProfileDeclarant::LOCATAIRE,
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
            'nbEnfantsM6' => 1,
            'nbOccupantsLogement' => 1,
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
            'modifiedAt' => new \DateTimeImmutable(),
            'closedAt' => new \DateTimeImmutable(),
            'motifCloture' => MotifCloture::INSALUBRITE,
            'familleSituation' => "l'état et propreté du logement|l'état et propreté du logement|",
            'desordres' => "Les sols sont humides.|Les installations électriques ne sont pas en bon état.|
                Les murs ont des fissures.|De l'eau s’infiltre dans mon logement.|Il y a des trace ",
            'debutDesordres' => DebutDesordres::YEARS_1_TO_2,
            'etiquettes' => null,
            'geoloc' => '{"lat": "43.3426152", "lng": "5.3711848"}',
            'interventionsData' => 'PLANNED||2023-07-13 13:41:15||DONE||2024-06-09 10:00:00',
            'interventionOccupantPresent' => '||1',
            'interventionConcludeProcedure' => '||RSD,INSALUBRITE',
            'interventionDetails' => '||dossier envoyé pour manquement sanitaire',
        ];

        $user = $this->getUserFromRole(User::ROLE_ADMIN);

        $signalementExportFactory = (new SignalementExportFactory())->createInstanceFrom($user, $data);
        $this->assertInstanceOf(SignalementExport::class, $signalementExportFactory);
        $this->assertEquals('en cours', $signalementExportFactory->statut);
        $this->assertEquals(MotifCloture::INSALUBRITE->label(), $signalementExportFactory->motifCloture);
        $this->assertEquals(ProfileDeclarant::LOCATAIRE->label(), $signalementExportFactory->typeDeclarant);

        $dateFormatted = (new \DateTimeImmutable())->format(SignalementExportFactory::DATE_FORMAT);
        $this->assertEquals($dateFormatted, $signalementExportFactory->createdAt);
        $this->assertEquals($dateFormatted, $signalementExportFactory->modifiedAt);
        $this->assertEquals($dateFormatted, $signalementExportFactory->closedAt);
        $this->assertEquals('09/06/2024', $signalementExportFactory->dateVisite);
        $this->assertEquals('Oui', $signalementExportFactory->isOccupantPresentVisite);
        $this->assertEquals(2, $signalementExportFactory->nbVisites);

        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->telephoneOccupantBis);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->etageOccupant);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->escalierOccupant);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->numAppartOccupant);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->adresseAutreOccupant);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->numAllocataire);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->natureLogement);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->superficie);

        $this->assertEquals('-', $signalementExportFactory->photos);
        $this->assertEquals('-', $signalementExportFactory->documents);
        $this->assertEquals(SignalementExportFactory::OUI, $signalementExportFactory->isProprioAverti);
        $this->assertEquals(SignalementExportFactory::OUI, $signalementExportFactory->isProprioAverti);
        $this->assertEquals(SignalementExportFactory::OUI, $signalementExportFactory->isAllocataire);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->isLogementSocial);
        $this->assertEquals(SignalementExportFactory::NON, $signalementExportFactory->isPreavisDepart);
        $this->assertEquals(SignalementExportFactory::NON_RENSEIGNE, $signalementExportFactory->isRelogement);
        $this->assertEquals(SignalementExportFactory::NON, $signalementExportFactory->isNotOccupant);
        $this->assertEquals(VisiteStatus::TERMINEE->value, $signalementExportFactory->interventionStatus);
        $this->assertEquals('RSD,INSALUBRITE', $signalementExportFactory->interventionConcludeProcedure);
        $this->assertEquals('colonne temporairement désactivée', $signalementExportFactory->interventionDetails);
        $this->assertEquals('Entre 1 et 2 ans', $signalementExportFactory->debutDesordres);
    }
}
