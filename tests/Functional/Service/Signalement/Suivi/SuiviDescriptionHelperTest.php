<?php

namespace App\Tests\Functional\Service\Signalement\Suivi;

use App\Entity\Enum\MotifClotureUsager;
use App\Entity\Enum\SuiviCategory;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\Suivi\SuiviDescriptionVariant;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function provideNewDocumentContexts(): \Generator
    {
        yield 'procédure, 1 document' => ['procedure', 1, '1 document lié à la procédure a été ajouté au signalement.'];
        yield 'procédure, plusieurs documents' => ['procedure', 3, '3 documents liés à la procédure ont été ajoutés au signalement.'];
        yield 'situation, 1 document' => ['situation', 1, '1 document sur la situation usager a été ajouté au signalement :'];
        yield 'situation, plusieurs documents' => ['situation', 2, '2 documents sur la situation usager ont été ajoutés au signalement :'];
        yield 'rapport de visite, 1 document' => ['rapport_visite', 1, 'Partenaire 13 a ajouté 1 rapport de visite de la visite du 15/03/2026 :'];
        yield 'rapports de visite, plusieurs documents' => ['rapport_visite', 2, 'Partenaire 13 a ajouté 2 rapports de visite de la visite du 15/03/2026 :'];
        yield 'photo de visite, 1 document' => ['photo_visite', 1, 'Partenaire 13 a ajouté 1 photo de la visite du 15/03/2026 :'];
        yield 'photos de visite, plusieurs documents' => ['photo_visite', 4, 'Partenaire 13 a ajouté 4 photos de la visite du 15/03/2026 :'];
        yield 'type de document sans texte' => [null, 2, ''];
        yield 'aucun document' => ['procedure', 0, ''];
    }

    #[DataProvider('provideNewDocumentContexts')]
    public function testBuildDescriptionForCreationNewDocument(?string $context, int $nbDocs, string $expected): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::NEW_DOCUMENT, [
            'context' => $context,
            'nbDocs' => $nbDocs,
            'partnerName' => 'Partenaire 13',
            'dateVisite' => '15/03/2026',
        ]);

        $this->assertSame($expected, $description);
    }

    public function testBuildDescriptionForCreationNewDocumentEscapesPartnerName(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::NEW_DOCUMENT, [
            'context' => 'photo_visite',
            'nbDocs' => 1,
            'partnerName' => 'Mairie d\'Arles <b>',
            'dateVisite' => '15/03/2026',
        ]);

        $this->assertSame('Mairie d&#039;Arles &lt;b&gt; a ajouté 1 photo de la visite du 15/03/2026 :', $description);
    }

    public function testBuildDescriptionForCreationAskDocumentWithAllSections(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::ASK_DOCUMENT, [
            'documents' => 'Bail / DPE',
            'desordres' => 'Humidité',
            'hasCoordonneesBailleur' => false,
        ]);

        $this->assertSame(
            'Bonjour,<br><br>Vous avez signalé un problème sur un logement.<br>'
            .'Votre dossier a bien été enregistré par nos services.<br><br>'
            .'Afin de nous aider à traiter au mieux votre dossier, veuillez nous fournir :<br>'
            .'- le ou les documents suivants : Bail / DPE<br>'
            .'- des photos pour les désordres suivants : Humidité<br>'
            .'- les coordonnées du propriétaire / bailleur (numéro de téléphone et / ou e-mail). '
            .'Pour cela, retournez sur l\'accueil de votre dossier et cliquez sur "Voir et compléter le dossier".<br>'
            .'<br>Envoyez-nous un message en y ajoutant vos documents !<br>Merci,<br>L\'équipe '.$this->platformName,
            $description
        );
    }

    public function testBuildDescriptionForCreationAskDocumentWithoutOptionalSections(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::ASK_DOCUMENT, [
            'documents' => '',
            'desordres' => '',
            'hasCoordonneesBailleur' => true,
        ]);

        $this->assertStringNotContainsString('- ', $description);
        $this->assertStringContainsString('veuillez nous fournir :<br><br>Envoyez-nous un message', $description);
    }

    public function testBuildDescriptionForCreationAskDocumentWithOnlyDesordres(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::ASK_DOCUMENT, [
            'documents' => '',
            'desordres' => 'Propreté et entretien / Sécurité',
            'hasCoordonneesBailleur' => true,
        ]);

        $this->assertStringNotContainsString('le ou les documents suivants', $description);
        $this->assertStringContainsString('fournir :<br>- des photos pour les désordres suivants : Propreté et entretien / Sécurité<br><br>', $description);
        $this->assertStringNotContainsString('coordonnées du propriétaire', $description);
    }

    public function testBuildDescriptionForCreationStatusSynchroAcceptedKeepsPrefixUsedForResynchronisation(): void
    {
        $description = $this->buildStatusSynchro(['action' => 'accepted', 'dossNum' => '2023/SISH/0010']);

        // EsaboraManager recherche ce préfixe pour détecter une resynchronisation
        $this->assertStringStartsWith('Signalement <b>accepté par Partenaire 13-01 via SISH', $description);
        $this->assertSame('Signalement <b>accepté par Partenaire 13-01 via SISH (Dossier 2023/SISH/0010)</b>', $description);
    }

    public static function provideStatusSynchroActions(): \Generator
    {
        yield 'remis en attente' => ['wait', 'Signalement <b>remis en attente par Partenaire 13-01 via SISH</b>'];
        yield 'resynchronisé' => ['resynchronized', 'Signalement <b>resynchronisé avec Partenaire 13-01 via SISH</b>'];
        yield 'clôturé' => ['closed', 'Signalement <b>clôturé par Partenaire 13-01 via SISH</b>'];
        yield 'refusé' => ['refused', 'Signalement <b>refusé par Partenaire 13-01 via SISH</b>'];
    }

    #[DataProvider('provideStatusSynchroActions')]
    public function testBuildDescriptionForCreationStatusSynchro(string $action, string $expected): void
    {
        $this->assertSame($expected, $this->buildStatusSynchro(['action' => $action]));
    }

    public function testBuildDescriptionForCreationStatusSynchroRejectedWithNoteAndCommentaire(): void
    {
        $description = $this->buildStatusSynchro([
            'action' => 'rejected',
            'causeRefus' => 'Doublon',
            'dateReaffectation' => '17/10/2026',
            'commentaireSas' => 'Déjà traité',
            'dossNum' => '2023/SISH/0010',
        ]);

        $this->assertSame(
            'Signalement <b>refusé via SISH pour motif suivant : Doublon'
            .'<br><i>NOTE : vous pourrez, si besoin, réaffecter ce signalement à partir du : 17/10/2026</i>'
            .'<br>Commentaire Santé-Habitat : Déjà traité (Dossier 2023/SISH/0010)</b>',
            $description
        );
    }

    public static function provideStatusSynchroIdoss(): \Generator
    {
        yield 'accepté' => ['accepted', null, 'Le signalement a été accepté par IDOSS'];
        yield 'clôturé' => ['closed', 'Travaux réalisés', 'Le signalement a été clôturé par IDOSS avec le motif suivant : "Travaux réalisés"'];
        yield 'mis à jour' => ['updated', null, 'Le signalement a été mis à jour ("EN_ATTENTE") par IDOSS'];
    }

    #[DataProvider('provideStatusSynchroIdoss')]
    public function testBuildDescriptionForCreationStatusSynchroIdoss(string $action, ?string $motif, string $expected): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(
            SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO,
            ['action' => $action, 'motif' => $motif, 'statut' => 'EN_ATTENTE'],
            SuiviDescriptionVariant::IDOSS,
        );

        $this->assertSame($expected, $description);
    }

    public function testBuildDescriptionForCreationMessageEsaboraSchs(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::MESSAGE_ESABORA_SCHS, [
            'date' => '15/03/2026',
            'presentation' => 'Visite effectuée',
            'libelle' => 'Rapport transmis',
        ]);

        $this->assertSame(
            'Date : 15/03/2026<br/>Message provenant d\'esabora SCHS :<br/>Visite effectuée<br/>Rapport transmis',
            $description
        );
    }

    public function testBuildDescriptionForCreationMessageEsaboraSchsWithoutPresentation(): void
    {
        $description = $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::MESSAGE_ESABORA_SCHS, [
            'date' => 'inconnue',
            'presentation' => null,
            'libelle' => 'Rapport transmis',
        ]);

        $this->assertSame('Date : inconnue<br/>Message provenant d\'esabora SCHS :<br/>Rapport transmis', $description);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildStatusSynchro(array $params): string
    {
        return $this->suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO, array_merge([
            'partnerName' => 'Partenaire 13-01',
            'nameSI' => 'SISH',
            'causeRefus' => null,
            'dateReaffectation' => null,
            'commentaireSas' => null,
            'dossNum' => null,
        ], $params));
    }
}
