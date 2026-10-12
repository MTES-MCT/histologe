<?php

namespace App\Tests\Functional\Service\Signalement\Suivi;

use App\Entity\Enum\MotifClotureUsager;
use App\Entity\Enum\SuiviCategory;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\Suivi\SuiviDescriptionVariant;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviDescriptionHelperTest extends KernelTestCase
{
    private SuiviDescriptionHelper $suiviDescriptionHelper;
    private string $platformName;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->suiviDescriptionHelper = static::getContainer()->get(SuiviDescriptionHelper::class);
        $this->platformName = static::getContainer()->getParameter('platform_name');
    }

    public function testBuildDescriptionForCreationDemandePoursuiteProcedure(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::DEMANDE_POURSUITE_PROCEDURE,
            ['nomComplet' => 'Jean DUPONT', 'details' => 'Je souhaite continuer'],
        );

        $this->assertSame(
            'Jean DUPONT a indiqué vouloir poursuivre la procédure sur '.$this->platformName.'<br>Commentaire : Je souhaite continuer',
            $description
        );
    }

    public function testBuildDescriptionForCreationDemandePoursuiteProcedureEscapesUsagerInput(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::DEMANDE_POURSUITE_PROCEDURE,
            ['nomComplet' => 'Jeanne D\'Arc', 'details' => "Ligne 1 <b>test</b>\nLigne 2"],
        );

        $this->assertStringStartsWith('Jeanne D&#039;Arc a indiqué', $description);
        $this->assertStringEndsWith("Commentaire : Ligne 1 &lt;b&gt;test&lt;/b&gt;<br />\nLigne 2", $description);
        $this->assertStringNotContainsString('<b>', $description);
    }

    public function testBuildDescriptionForCreationDemandePoursuiteProcedureWithoutDetails(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::DEMANDE_POURSUITE_PROCEDURE,
            ['nomComplet' => 'Jean DUPONT', 'details' => null],
        );

        $this->assertStringEndsWith('<br>Commentaire :', $description);
    }

    public function testBuildDescriptionForCreationDemandeAbandonProcedure(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::DEMANDE_ABANDON_PROCEDURE,
            [
                'nomComplet' => 'Jean DUPONT',
                'motif' => MotifClotureUsager::RELOGEMENT_OCCUPANT->label(),
                'details' => 'Nous avons déménagé',
            ],
        );

        $this->assertSame(
            'Jean DUPONT souhaite fermer son dossier sur '.$this->platformName
            .' pour le motif suivant : '.MotifClotureUsager::RELOGEMENT_OCCUPANT->label()
            .'<br>Détails du motif d\'arrêt de procédure : Nous avons déménagé',
            $description
        );
    }

    public function testBuildDescriptionForCreationDemandeAbandonProcedureEscapesUsagerInput(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::DEMANDE_ABANDON_PROCEDURE,
            [
                'nomComplet' => 'Jeanne D\'Arc',
                'motif' => 'Accord avec l\'occupant',
                'details' => "C'est <b>résolu</b>\nMerci",
            ],
        );

        $this->assertStringStartsWith('Jeanne D&#039;Arc souhaite fermer son dossier', $description);
        $this->assertStringContainsString('pour le motif suivant : Accord avec l&#039;occupant<br>', $description);
        $this->assertStringEndsWith("arrêt de procédure : C&#039;est &lt;b&gt;résolu&lt;/b&gt;<br />\nMerci", $description);
        $this->assertStringNotContainsString('<b>', $description);
    }

    public function testBuildDescriptionForCreationDemandeAbandonProcedureWithUnknownVariantThrows(): void
    {
        $this->expectException(\LogicException::class);

        $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::DEMANDE_ABANDON_PROCEDURE,
            ['nomComplet' => 'Jean DUPONT', 'motif' => 'Autre', 'details' => 'Nous avons déménagé'],
            SuiviDescriptionVariant::REPONSE_DEMANDE_CLOTURE,
        );
    }

    public function testBuildDescriptionForCreationDemandePoursuiteProcedureWithUnknownVariantThrows(): void
    {
        $this->expectException(\LogicException::class);

        $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::DEMANDE_POURSUITE_PROCEDURE,
            ['nomComplet' => 'Jean DUPONT', 'details' => 'Je souhaite continuer'],
            SuiviDescriptionVariant::REPONSE_DEMANDE_CLOTURE,
        );
    }
}
