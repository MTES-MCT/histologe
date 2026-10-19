<?php

namespace App\Tests\Unit\Service\Intervention;

use App\Entity\Intervention;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class InterventionDescriptionGeneratorTest extends TestCase
{
    use FixturesHelper;

    public function testArreteDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->getDossierArreteSISHCollectionResponse()->getCollection()[0];
        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);

        $this->assertStringContainsString('AR 123456-0215', $description, 'N° arrêté modificatif incorrect');
        $this->assertStringContainsString('07/08/2023', $description, 'Date arrêté modificatif incorrecte');
        $this->assertStringContainsString('n°2023/DD13/0010', $description, 'N° dossier incorrect');
        $this->assertStringContainsString('2023-DD13-00172', $description, 'N° main levée incorrect');
        $this->assertStringContainsString('01/07/2023', $description, 'Date de main levée incorrecte');
    }

    public function testArreteInitialDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getDossNum')->willReturn('2023/DD13/0010');
        $dossierArreteSISH->method('getArreteType')->willReturn('Arrêté L.511-11 - Suroccupation');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn(null);
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn(null);
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn(null);

        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        $this->assertEquals("L'arrêté 2023/DD13/00664 du 14/06/2023 a été pris dans le dossier de n°2023/DD13/0010.<br>Type arrêté : Arrêté L.511-11 - Suroccupation<br>", $description);
    }

    public function testArreteModificatifDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM001');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn(null);

        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        $this->assertEquals("Un arrêté modificatif AM001 en date du 01/07/2023 a été pris concernant l'arrêté 2023/DD13/00664 du 14/06/2023.", $description);
    }

    public function testArreteMainLeveeSansModificatifDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('07/08/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('ML001');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn(null);
        $dossierArreteSISH->method('getDossNum')->willReturn('2023/DD13/0010');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        $this->assertEquals("Un arrêté de mainlevée ML001 du 07/08/2023 a été pris pour l'arrêté 2023/DD13/00664 du 14/06/2023 dans le dossier de n°2023/DD13/0010.", $description);
    }

    public function testArreteMainLeveeAvecModificatifDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM001');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('07/08/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('ML001');
        $dossierArreteSISH->method('getDossNum')->willReturn('2023/DD13/0010');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        $this->assertEquals("Un arrêté de mainlevée ML001 du 07/08/2023 a été pris pour l'arrêté modificatif AM001 du 01/07/2023 dans le dossier de n°2023/DD13/0010.", $description);
    }

    public function testArreteInitialWithModificatifNumeroWithoutDateDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getDossNum')->willReturn('2023/DD13/0010');
        $dossierArreteSISH->method('getArreteType')->willReturn('Arrêté L.511-11 - Suroccupation');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn(null);
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn(null);
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM001');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn(null);

        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        $this->assertEquals("L'arrêté 2023/DD13/00664 du 14/06/2023 a été pris dans le dossier de n°2023/DD13/0010.<br>Type arrêté : Arrêté L.511-11 - Suroccupation<br>", $description);
    }

    public function testArreteMainLeveeAvecModificatifNumeroSansDateDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM001');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn(null);
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('07/08/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('ML001');
        $dossierArreteSISH->method('getDossNum')->willReturn('2023/DD13/0010');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        $this->assertEquals("Un arrêté de mainlevée ML001 du 07/08/2023 a été pris pour l'arrêté 2023/DD13/00664 du 14/06/2023 dans le dossier de n°2023/DD13/0010.", $description);
    }

    public function testArreteDescriptionOnInterventionUpdated(): void
    {
        $oldAdditionalInformation = [
            'arrete_numero' => '2023/DD13/00664',
            'arrete_type' => 'INSALUBRITE',
            'arrete_mainlevee_date' => '01/07/2023',
            'arrete_mainlevee_numero' => '2023-DD13-00172',
        ];
        $intervention = new Intervention()
            ->setScheduledAt(new \DateTimeImmutable('2023-06-14'))
            ->setAdditionalInformation($oldAdditionalInformation);

        // Test modification date et numéro arrêté
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00665');
        $dossierArreteSISH->method('getArreteDate')->willReturn('15/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('La date de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 15/06/2023.<br>Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665.<br>Type arrêté : ', $description);

        // Test modification date seule arrêté
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('15/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('La date de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 15/06/2023.<br>Type arrêté : ', $description);

        // Test modification numéro seule arrêté
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00665');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665.<br>Type arrêté : ', $description);

        // Test modification date et numéro mainlevée
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('02/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00173');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('La date de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023.<br>Le numéro de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023-DD13-00173.<br>Type arrêté : ', $description);

        // Test modification les deux (arrêté et mainlevée)
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00665');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('02/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665.<br>La date de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023.<br>Type arrêté : ', $description);

        // Test modification numéro arrêté vers null (avec informations ML dans le dossier)
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn(null);
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est non renseigné.<br>Type arrêté : ', $description);

        // Test modification numéro arrêté vers null avec champs ML null (cas du premier élément split)
        $dossierArreteSISHSplit = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISHSplit->method('getArreteNumero')->willReturn(null);
        $dossierArreteSISHSplit->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISHSplit->method('getArreteMLDate')->willReturn(null);
        $dossierArreteSISHSplit->method('getArreteMLNumero')->willReturn(null);

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISHSplit);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est non renseigné.<br>Type arrêté : ', $description);

        // Test modification numéro arrêté depuis null vers une valeur
        $interventionWithNullNumero = new Intervention()
            ->setScheduledAt(new \DateTimeImmutable('2023-06-14'))
            ->setAdditionalInformation([
                'arrete_numero' => null,
                'arrete_type' => 'INSALUBRITE',
                'arrete_mainlevee_date' => '01/07/2023',
                'arrete_mainlevee_numero' => '2023-DD13-00172',
            ]);

        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('AP45OL023');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($interventionWithNullNumero, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AP45OL023.<br>Type arrêté : ', $description);

        // Test modification numéro mainlevée vers null
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn(null);

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('Le numéro de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est non renseigné.<br>Type arrêté : ', $description);

        // Test modification numéro mainlevée depuis null vers une valeur
        $interventionWithNullMLNumero = new Intervention()
            ->setScheduledAt(new \DateTimeImmutable('2023-06-14'))
            ->setAdditionalInformation([
                'arrete_numero' => '2023/DD13/00664',
                'arrete_type' => 'INSALUBRITE',
                'arrete_mainlevee_date' => '01/07/2023',
                'arrete_mainlevee_numero' => null,
            ]);

        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('APML45K09O');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($interventionWithNullMLNumero, $dossierArreteSISH);
        $this->assertEquals('Le numéro de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est APML45K09O.<br>Type arrêté : ', $description);

        // Test modification date et numéro arrêté modificatif
        $interventionWithModif = new Intervention()
            ->setScheduledAt(new \DateTimeImmutable('2023-06-14'))
            ->setAdditionalInformation([
                'arrete_numero' => '2023/DD13/00664',
                'arrete_type' => 'INSALUBRITE',
                'arrete_modificatif_date' => '01/07/2023',
                'arrete_modificatif_numero' => 'AM001',
            ]);

        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn('02/07/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM002');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($interventionWithModif, $dossierArreteSISH);
        $this->assertEquals('La date de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023.<br>Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AM002.<br>Type arrêté : ', $description);

        // Test modification date seule arrêté modificatif
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn('02/07/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM001');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($interventionWithModif, $dossierArreteSISH);
        $this->assertEquals('La date de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023.<br>Type arrêté : ', $description);

        // Test modification numéro seul arrêté modificatif
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM002');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($interventionWithModif, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AM002.<br>Type arrêté : ', $description);

        // Test modification numéro arrêté modificatif vers null
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn(null);

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($interventionWithModif, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est non renseigné.<br>Type arrêté : ', $description);

        // Test modification numéro arrêté modificatif depuis null vers une valeur
        $interventionWithNullModifNumero = new Intervention()
            ->setScheduledAt(new \DateTimeImmutable('2023-06-14'))
            ->setAdditionalInformation([
                'arrete_numero' => '2023/DD13/00664',
                'arrete_type' => 'INSALUBRITE',
                'arrete_modificatif_date' => '01/07/2023',
                'arrete_modificatif_numero' => null,
            ]);

        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM001');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($interventionWithNullModifNumero, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AM001.<br>Type arrêté : ', $description);

        // Test modificatif avec numéro mais sans date : ignoré, ne génère aucune modification
        $interventionWithoutModif = new Intervention()
            ->setScheduledAt(new \DateTimeImmutable('2023-06-14'))
            ->setAdditionalInformation([
                'arrete_numero' => '2023/DD13/00664',
                'arrete_type' => 'INSALUBRITE',
                'arrete_modificatif_date' => null,
                'arrete_modificatif_numero' => null,
            ]);

        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn(null);
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM001');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($interventionWithoutModif, $dossierArreteSISH);
        $this->assertNull($description);
    }

    public function testArreteDescriptionOnMainLeveeCreated(): void
    {
        $oldAdditionalInformation = [
            'arrete_numero' => '2023/DD13/00664',
            'arrete_type' => 'INSALUBRITE',
            'arrete_mainlevee_date' => null,
            'arrete_mainlevee_numero' => null,
        ];

        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/02/2026');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('APML45K09O');
        $dossierArreteSISH->method('getDossNum')->willReturn('2023/DD13/0010');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        $this->assertStringContainsString('Un arrêté de mainlevée APML45K09O du 01/02/2026 a été pris pour l\'arrêté 2023/DD13/00664 du 14/06/2023 dans le dossier de n°2023/DD13/0010.', $description);
    }
}
