<?php

namespace App\Tests\Unit\Service\Arrete;

use App\Entity\Arrete;
use App\Entity\Enum\ArreteType;
use App\Service\Arrete\ArreteDescriptionGenerator;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class ArreteDescriptionGeneratorTest extends TestCase
{
    use FixturesHelper;

    public function testArreteDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->getDossierArreteSISHCollectionResponse()->getCollection()[0];
        $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($this->createArreteFromDossier($dossierArreteSISH));

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

        $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($this->createArreteFromDossier($dossierArreteSISH));
        $this->assertEquals("L'arrêté 2023/DD13/00664 du 14/06/2023 a été pris dans le dossier de n°2023/DD13/0010.<br>Type arrêté : Suroccupation<br>", $description);
    }

    public function testArreteModificatifDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteModificatifNumero')->willReturn('AM001');
        $dossierArreteSISH->method('getArreteModificatifDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn(null);

        $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($this->createArreteFromDossier($dossierArreteSISH));
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

        $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($this->createArreteFromDossier($dossierArreteSISH));
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

        $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($this->createArreteFromDossier($dossierArreteSISH));
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

        $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($this->createArreteFromDossier($dossierArreteSISH));
        $this->assertEquals("L'arrêté 2023/DD13/00664 du 14/06/2023 a été pris dans le dossier de n°2023/DD13/0010.<br>Type arrêté : Suroccupation<br>", $description);
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

        $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($this->createArreteFromDossier($dossierArreteSISH));
        $this->assertEquals("Un arrêté de mainlevée ML001 du 07/08/2023 a été pris pour l'arrêté 2023/DD13/00664 du 14/06/2023 dans le dossier de n°2023/DD13/0010.", $description);
    }

    public function testArreteDescriptionOnInterventionUpdated(): void
    {
        $oldAdditionalInformation = [
            'arrete_numero' => '2023/DD13/00664',
            'arrete_mainlevee_date' => '01/07/2023',
            'arrete_mainlevee_numero' => '2023-DD13-00172',
        ];
        $arrete = $this->createArrete($oldAdditionalInformation);
        $typeLine = 'Type arrêté : '.$arrete->getArreteType()->label();

        // Test modification date et numéro arrêté
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, [
            'arrete_date' => '15/06/2023',
            'arrete_numero' => '2023/DD13/00665',
        ]);
        $this->assertEquals('La date de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 15/06/2023.<br>Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665.<br>'.$typeLine, $description);

        // Test modification date seule arrêté
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, [
            'arrete_date' => '15/06/2023',
        ]);
        $this->assertEquals('La date de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 15/06/2023.<br>'.$typeLine, $description);

        // Test modification numéro seule arrêté
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, [
            'arrete_numero' => '2023/DD13/00665',
        ]);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665.<br>'.$typeLine, $description);

        // Test modification date et numéro mainlevée
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, [
            'arrete_mainlevee_date' => '02/07/2023',
            'arrete_mainlevee_numero' => '2023-DD13-00173',
        ]);
        $this->assertEquals('La date de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023.<br>Le numéro de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023-DD13-00173.<br>'.$typeLine, $description);

        // Test modification les deux (arrêté et mainlevée)
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, [
            'arrete_numero' => '2023/DD13/00665',
            'arrete_mainlevee_date' => '02/07/2023',
        ]);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665.<br>La date de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023.<br>'.$typeLine, $description);

        // Test modification numéro arrêté vers null (avec informations ML dans le dossier)
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, [
            'arrete_numero' => null,
        ]);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est non renseigné.<br>'.$typeLine, $description);

        // Test modification numéro arrêté vers null avec champs ML null (cas du premier élément split)
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, [
            'arrete_numero' => null,
        ]);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est non renseigné.<br>'.$typeLine, $description);

        // Test modification numéro arrêté depuis null vers une valeur
        $arreteWithNullNumero = $this->createArrete([
            'arrete_numero' => null,
            'arrete_mainlevee_date' => '01/07/2023',
            'arrete_mainlevee_numero' => '2023-DD13-00172',
        ]);
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arreteWithNullNumero, [
            'arrete_numero' => 'AP45OL023',
        ]);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AP45OL023.<br>'.$typeLine, $description);

        // Test modification numéro mainlevée vers null
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arrete, [
            'arrete_mainlevee_numero' => null,
        ]);
        $this->assertEquals('Le numéro de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est non renseigné.<br>'.$typeLine, $description);

        // Test modification numéro mainlevée depuis null vers une valeur
        $arreteWithNullMLNumero = $this->createArrete([
            'arrete_numero' => '2023/DD13/00664',
            'arrete_mainlevee_date' => '01/07/2023',
            'arrete_mainlevee_numero' => null,
        ]);
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arreteWithNullMLNumero, [
            'arrete_mainlevee_numero' => 'APML45K09O',
        ]);
        $this->assertEquals('Le numéro de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est APML45K09O.<br>'.$typeLine, $description);

        // Test modification date et numéro arrêté modificatif
        $arreteWithModif = $this->createArrete([
            'arrete_numero' => '2023/DD13/00664',
            'arrete_modificatif_date' => '01/07/2023',
            'arrete_modificatif_numero' => 'AM001',
        ]);
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arreteWithModif, [
            'arrete_modificatif_date' => '02/07/2023',
            'arrete_modificatif_numero' => 'AM002',
        ]);
        $this->assertEquals('La date de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023.<br>Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AM002.<br>'.$typeLine, $description);

        // Test modification date seule arrêté modificatif
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arreteWithModif, [
            'arrete_modificatif_date' => '02/07/2023',
        ]);
        $this->assertEquals('La date de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023.<br>'.$typeLine, $description);

        // Test modification numéro seul arrêté modificatif
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arreteWithModif, [
            'arrete_modificatif_numero' => 'AM002',
        ]);
        $this->assertEquals('Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AM002.<br>'.$typeLine, $description);

        // Test modification numéro arrêté modificatif vers null
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arreteWithModif, [
            'arrete_modificatif_numero' => null,
        ]);
        $this->assertEquals('Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est non renseigné.<br>'.$typeLine, $description);

        // Test modification numéro arrêté modificatif depuis null vers une valeur
        $arreteWithNullModifNumero = $this->createArrete([
            'arrete_numero' => '2023/DD13/00664',
            'arrete_modificatif_date' => '01/07/2023',
            'arrete_modificatif_numero' => null,
        ]);
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arreteWithNullModifNumero, [
            'arrete_modificatif_numero' => 'AM001',
        ]);
        $this->assertEquals('Le numéro de l\'arrêté modificatif dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est AM001.<br>'.$typeLine, $description);

        // Test modificatif avec numéro mais sans date : ignoré, ne génère aucune modification
        $arreteWithoutModif = $this->createArrete([
            'arrete_numero' => '2023/DD13/00664',
            'arrete_modificatif_date' => null,
            'arrete_modificatif_numero' => null,
        ]);
        $description = ArreteDescriptionGenerator::buildDescriptionArreteUpdated($arreteWithoutModif, []);
        $this->assertNull($description);
    }

    public function testArreteDescriptionOnMainLeveeCreated(): void
    {
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/02/2026');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('APML45K09O');
        $dossierArreteSISH->method('getDossNum')->willReturn('2023/DD13/0010');

        $description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($this->createArreteFromDossier($dossierArreteSISH));
        $this->assertStringContainsString('Un arrêté de mainlevée APML45K09O du 01/02/2026 a été pris pour l\'arrêté 2023/DD13/00664 du 14/06/2023 dans le dossier de n°2023/DD13/0010.', $description);
    }

    /**
     * @param array<string, string|null> $additionalInformation
     */
    private function createArrete(array $additionalInformation, string $dateArrete = '14/06/2023', ?string $dateMainLevee = null): Arrete
    {
        return (new Arrete())
            ->setDateArrete(\DateTimeImmutable::createFromFormat('d/m/Y', $dateArrete) ?: new \DateTimeImmutable())
            ->setDateMainLevee($dateMainLevee ? (\DateTimeImmutable::createFromFormat('d/m/Y', $dateMainLevee) ?: null) : null)
            ->setArreteType(ArreteType::ARRETE_L_511_19_INSALUBRITE)
            ->setAdditionalInformation($additionalInformation);
    }

    private function createArreteFromDossier(DossierArreteSISH $dossierArreteSISH): Arrete
    {
        $arrete = $this->createArrete(
            [
                'numero_dossier' => $dossierArreteSISH->getDossNum(),
                'arrete_numero' => $dossierArreteSISH->getArreteNumero(),
                'arrete_mainlevee_numero' => $dossierArreteSISH->getArreteMLNumero(),
                'arrete_modificatif_date' => $dossierArreteSISH->getArreteModificatifDate(),
                'arrete_modificatif_numero' => $dossierArreteSISH->getArreteModificatifNumero(),
            ],
            (string) $dossierArreteSISH->getArreteDate(),
            $dossierArreteSISH->getArreteMLDate(),
        );

        return $arrete->setArreteType(ArreteType::fromEsaboraLabel(
            $dossierArreteSISH->getArreteType() ?? 'Arrêté L.511-11 - Suroccupation'
        ));
    }
}
