<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Repository\SignalementRepository;
use App\Repository\SuiviDelayedRepository;
use App\Repository\UserRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Ban\Response\BanAddress;
use App\Service\MessageHelper;
use App\Tests\SessionHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;

class SignalementEditControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private SuiviDelayedRepository $suiviDelayedRepository;
    private RouterInterface $router;
    private ?Signalement $signalement = null;
    private ?string $featureOrientation = null;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        // TODO à la suppression de FEATURE_ORIENTATION : supprimer le forçage du flag (ici et dans tearDown)
        $this->featureOrientation = $_ENV['FEATURE_ORIENTATION'] ?? null;
        $_ENV['FEATURE_ORIENTATION'] = '1';
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->suiviDelayedRepository = static::getContainer()->get(SuiviDelayedRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
        $this->signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
    }

    protected function tearDown(): void
    {
        if (null === $this->featureOrientation) {
            unset($_ENV['FEATURE_ORIENTATION']);
        } else {
            $_ENV['FEATURE_ORIENTATION'] = $this->featureOrientation;
        }
        parent::tearDown();
    }

    // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce test
    public function testEditCoordonneesBailleurOldWithBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);

        $route = $this->router->generate(
            'back_signalement_edit_coordonnees_bailleur_old',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = $this->getPayloadCoordonneesBailleurOld('13 habitat', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();

        $this->assertEquals('13 HABITAT', $signalement->getBailleur()->getName());
        $this->assertEquals('13 HABITAT', $signalement->getDenominationProprio());
    }

    // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce test
    public function testEditCoordonneesBailleurOldWithCustomBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
        $route = $this->router->generate(
            'back_signalement_edit_coordonnees_bailleur_old',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = $this->getPayloadCoordonneesBailleurOld('Habitat Social Solidaire', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();
        $this->assertNull($signalement->getBailleur());
        $this->assertEquals('Habitat Social Solidaire', $signalement->getDenominationProprio());
    }

    public function testEditCoordonneesBailleurWithBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);
        $route = $this->router->generate(
            'back_signalement_edit_coordonnees_bailleur',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = $this->getPayloadCoordonneesBailleur('13 habitat', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();
        $this->assertEquals('13 HABITAT', $signalement->getBailleur()->getName());
        $this->assertEquals('13 HABITAT', $signalement->getDenominationProprio());
    }

    public function testEditCoordonneesBailleurWithCustomBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
        $route = $this->router->generate(
            'back_signalement_edit_coordonnees_bailleur',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = $this->getPayloadCoordonneesBailleur('Habitat Social Solidaire', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();
        $this->assertNull($signalement->getBailleur());
        $this->assertEquals('Habitat Social Solidaire', $signalement->getDenominationProprio());
    }

    public function testEditCoordonneesBailleurDoesNotOverwriteInformationsBailleur(): void
    {
        $this->client->disableReboot();
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);

        $route = $this->router->generate('back_signalement_edit_informations_bailleur', ['uuid' => $signalement->getUuid()]);
        $payload = $this->getPayloadInformationsBailleur('oui', 'non', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));
        $this->assertResponseIsSuccessful();

        $route = $this->router->generate('back_signalement_edit_coordonnees_bailleur', ['uuid' => $signalement->getUuid()]);
        $payload = $this->getPayloadCoordonneesBailleur('13 habitat', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));
        $this->assertResponseIsSuccessful();

        $informationComplementaire = $signalement->getInformationComplementaire();
        $this->assertEquals('oui', $informationComplementaire->getInformationsComplementairesSituationBailleurBeneficiaireRsa());
        $this->assertEquals('non', $informationComplementaire->getInformationsComplementairesSituationBailleurBeneficiaireFsl());
    }

    public function testEditInformationsBailleurWithEmptyValues(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);
        $route = $this->router->generate('back_signalement_edit_informations_bailleur', ['uuid' => $signalement->getUuid()]);

        $payload = $this->getPayloadInformationsBailleur('', '', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();
        $informationComplementaire = $signalement->getInformationComplementaire();
        $this->assertNull($informationComplementaire->getInformationsComplementairesSituationBailleurBeneficiaireRsa());
        $this->assertNull($informationComplementaire->getInformationsComplementairesSituationBailleurBeneficiaireFsl());
        $this->assertNull($informationComplementaire->getInformationsComplementairesSituationBailleurRevenuFiscal());
    }

    public function testInviteTiers(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000001']);
        $route = $this->router->generate(
            'back_signalement_edit_invite_tiers',
            ['uuid' => $signalement->getUuid()]
        );

        $newMail = 'paulpote@gmail.com';

        $payload = [
            'nom' => 'Pote',
            'prenom' => 'Paul',
            'mail' => $newMail,
            '_token' => $this->getCsrfToken('signalement_edit_invite_tiers_', $signalement->getId()),
        ];
        $this->client->request(
            method: 'POST',
            uri: $route,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
        $this->assertNull($signalement->getMailDeclarant());
        $this->assertNull($signalement->getIsCguTiersAccepted());
        $this->assertEmailCount(3);
        $this->assertEmailSubjectContains($this->getMailerMessages()[0], 'Invitation à suivre un dossier de signalement');
        $this->assertEmailSubjectContains($this->getMailerMessages()[1], 'Nouveau suivi');
        $this->assertEmailSubjectContains($this->getMailerMessages()[2], 'Nouvelle mise à jour de votre signalement !');

        // Test d'une seconde invitation alors qu'une invitation est déjà en attente
        $newMail = 'paulpot2@gmail.com';
        $payload = [
            'nom' => 'Pote2',
            'prenom' => 'Paul',
            'mail' => $newMail,
            '_token' => $this->getCsrfToken('signalement_edit_invite_tiers_', $signalement->getId()),
        ];
        $this->client->request(
            method: 'POST',
            uri: $route,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertTrue($response['stayOnPage']);
        $msgFlash = 'Une invitation est déjà en attente pour ce dossier.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    /**
     * @param array<string> $payload
     */
    #[DataProvider('provideEditSignalementRoutes')]
    public function testEditSignalementSuccess(string $routeName, array $payload, string $token): void
    {
        $addressResult = json_decode((string) file_get_contents(__DIR__.'/../../../files/datagouv/get_api_ban_item_response_13202.json'), true);
        $addressMock = $this->createMock(AddressService::class);
        $addressMock->method('getAddress')->willReturn(new BanAddress($addressResult));
        $this->client->getContainer()->set(AddressService::class, $addressMock);
        $route = $this->router->generate(
            $routeName,
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload['_token'] = $this->getCsrfToken($token, $this->signalement->getId());

        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();
    }

    /**
     * @param array<string> $payload
     */
    #[DataProvider('provideEditSignalementRoutes')]
    public function testEditSignalementUnauthorization(string $routeName, array $payload, string $token): void
    {
        $route = $this->router->generate(
            $routeName,
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload['_token'] = '1234';
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertTrue($response['stayOnPage']);
        $msgFlash = MessageHelper::ERROR_MESSAGE_CSRF;
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    /**
     * @param array<string> $payload
     */
    #[DataProvider('provideEditSignalementRoutes')]
    public function testEditSignalementError(string $routeName, array $payload, string $token): void
    {
        $route = $this->router->generate(
            $routeName,
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload['_token'] = $this->getCsrfToken($token, $this->signalement->getId());
        $payload[key($payload)] = str_repeat('x', 5000);
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testEditLogementVacantSuccess(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000009']);
        $route = $this->router->generate(
            'back_signalement_edit_logement_vacant',
            ['uuid' => $signalement->getUuid()]
        );

        $post = [
            'logementVacant' => '1',
            '_token' => $this->generateCsrfToken($this->client, 'signalement_switch_logement_vacant'),
        ];

        $this->client->request('POST', $route, $post);

        $this->assertResponseRedirects();
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000009']);
        $this->assertTrue($signalement->getIsLogementVacant());
        $suivDelayed = $this->suiviDelayedRepository->findOneBy(['signalement' => $signalement, 'suiviDelayedType' => SuiviDelayedType::BO_EDIT_OCCUPATION_LOGEMENT]);
        $this->assertNotNull($suivDelayed);
        $this->assertStringContainsString('Le logement a été marqué comme vacant.', $suivDelayed->getChanges()['description']);

        /** @var Session $session */
        $session = $this->client->getRequest()->getSession();
        $flashBag = $session->getFlashBag();
        $this->assertTrue($flashBag->has('success'));
        $successMessages = $flashBag->get('success');
        $this->assertEquals(['title' => 'Modifications enregistrées', 'message' => 'Le statut d\'occupation du logement a bien été modifié.'], $successMessages[0]);
    }

    public function testEditConsommationEnergetiqueBefore2023(): void
    {
        $route = $this->router->generate(
            'back_signalement_edit_consommation_energetique',
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload = [
            ...self::getStaticPayloadConsommationEnergetique(),
            'dateDernierDPE' => '1970-01-01',
            'consommationEnergie' => '30000',
            'superficie' => '60',
            '_token' => $this->getCsrfToken('signalement_edit_consommation_energetique_', $this->signalement->getId()),
        ];
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();
        $typeCompositionLogement = $this->signalement->getTypeCompositionLogement();
        $this->assertEquals('oui', $typeCompositionLogement->getBailDpeDpe());
        $this->assertEquals('F', $typeCompositionLogement->getBailDpeClasseEnergetique());
        $this->assertEquals('before2023', $typeCompositionLogement->getDesordresLogementChauffageDetailsDpeAnnee());
        $this->assertEquals('30000', $typeCompositionLogement->getDesordresLogementChauffageDetailsDpeConso());
        $this->assertEquals(60, $this->signalement->getSuperficie());
        $this->assertEquals('2021-05-01', $this->signalement->getDateEntree()->format('Y-m-d'));
    }

    public function testEditConsommationEnergetiqueUpdatesQualificationNde(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-8']);
        $route = $this->router->generate(
            'back_signalement_edit_consommation_energetique',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = [
            ...self::getStaticPayloadConsommationEnergetique(),
            'dateDernierDPE' => '2023-01-02',
            'consommationEnergie' => '500',
            '_token' => $this->getCsrfToken('signalement_edit_consommation_energetique_', $signalement->getId()),
        ];
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();
        $this->assertEquals('post2023', $signalement->getTypeCompositionLogement()->getDesordresLogementChauffageDetailsDpeAnnee());
        $this->assertEquals('500', $signalement->getTypeCompositionLogement()->getDesordresLogementChauffageDetailsDpeConsoFinale());

        $signalementQualificationNDE = $signalement->getSignalementQualifications()->filter(
            static fn (SignalementQualification $qualification) => Qualification::NON_DECENCE_ENERGETIQUE === $qualification->getQualification()
        )->first();
        $this->assertInstanceOf(SignalementQualification::class, $signalementQualificationNDE);
        $this->assertEquals(500, $signalementQualificationNDE->getDetails()['consommation_energie']);
        $this->assertEquals(QualificationStatus::NDE_AVEREE, $signalementQualificationNDE->getStatus());
    }

    public function testEditConsommationEnergetiqueUnauthorization(): void
    {
        $route = $this->router->generate(
            'back_signalement_edit_consommation_energetique',
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload = [...self::getStaticPayloadConsommationEnergetique(), '_token' => '1234'];
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['stayOnPage']);
        $this->assertEquals(MessageHelper::ERROR_MESSAGE_CSRF, $response['flashMessages'][0]['message']);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string>        $expectedTargets
     */
    #[DataProvider('provideEditSignalementRoutesWithLinkedTargets')]
    public function testEditSignalementReloadsLinkedTargets(string $routeName, array $payload, string $token, array $expectedTargets): void
    {
        $route = $this->router->generate(
            $routeName,
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload['_token'] = $this->getCsrfToken($token, $this->signalement->getId());
        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));

        $this->assertResponseIsSuccessful();
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $targets = array_column($response['htmlTargetContents'], 'target');
        foreach ($expectedTargets as $expectedTarget) {
            $this->assertContains($expectedTarget, $targets);
        }
    }

    public static function provideEditSignalementRoutesWithLinkedTargets(): \Generator
    {
        yield 'Edition Consommation énergétique' => [
            'back_signalement_edit_consommation_energetique',
            self::getStaticPayloadConsommationEnergetique(),
            'signalement_edit_consommation_energetique_',
            [
                '#signalement-consommation-energetique-container',
                '#signalement-occupation-logement-container',
                '#signalement-description-logement-container',
                '#occupationLogementDateEntree-container',
                '#compositionLogementSuperficie-container',
                '#signalement-pre-evaluation-container',
            ],
        ];

        yield 'Edition Occupation du logement' => [
            'back_signalement_edit_occupation_logement',
            array_diff_key(self::getStaticPayloadInformationLogement(), ['bailDpeDpe' => null, 'bailDpeClasseEnergetique' => null]),
            'signalement_edit_occupation_logement_',
            [
                '#signalement-occupation-logement-container',
                '#signalement-consommation-energetique-container',
                '#signalement-edit-consommation-energetique-date-entree-container',
                '#signalement-pre-evaluation-container',
            ],
        ];

        yield 'Edition Description du logement' => [
            'back_signalement_edit_composition_logement',
            [...self::getStaticPayloadCompositionLogement(), 'type' => 'appartement'],
            'signalement_edit_composition_logement_',
            [
                '#signalement-description-logement-container',
                '#signalement-occupation-logement-container',
                '#autresOccupantsDesordre-container',
                '#signalement-consommation-energetique-container',
                '#signalement-edit-consommation-energetique-superficie-container',
                '#signalement-pre-evaluation-container',
            ],
        ];

        yield 'Edition Situation du foyer' => [
            'back_signalement_edit_situation_foyer',
            [...self::getStaticPayloadSituationFoyer(), 'infoProcedureDepartApresTravaux' => 'non'],
            'signalement_edit_situation_foyer_',
            [
                '#signalement-situation-foyer-container',
                '#signalement-demarches-usager-container',
                '#signalement-pre-evaluation-container',
            ],
        ];

        yield 'Edition Procédure et démarches' => [
            'back_signalement_edit_procedure_demarches',
            array_diff_key(self::getStaticPayloadProcedureDemarches(), ['infoProcedureDepartApresTravaux' => null]),
            'signalement_edit_procedure_demarches_',
            [
                '#signalement-demarches-usager-container',
                '#signalement-pre-evaluation-container',
            ],
        ];
    }

    // TODO à la suppression de FEATURE_ORIENTATION : supprimer cette méthode
    /**
     * @return array<string>
     */
    private function getPayloadCoordonneesBailleurOld(string $bailleurName, int $signalementId): array
    {
        return [
            'denomination' => $bailleurName,
            'nom' => 'Bernard',
            'prenom' => '',
            'mail' => 'contact@13habitat.fr',
            'telephone' => '0611000000',
            'ville' => 'Marseille',
            'beneficiaireRsa' => '',
            'beneficiaireFsl' => '',
            'revenuFiscal' => '',
            'dateNaissance' => '',
            '_token' => $this->getCsrfToken('signalement_edit_coordonnees_bailleur_old_', $signalementId),
        ];
    }

    /**
     * @return array<string>
     */
    private function getPayloadCoordonneesBailleur(string $bailleurName, int $signalementId): array
    {
        return [
            'denomination' => $bailleurName,
            'nom' => 'Bernard',
            'prenom' => '',
            'mail' => 'contact@13habitat.fr',
            'telephone' => '0611000000',
            'ville' => 'Marseille',
            '_token' => $this->getCsrfToken('signalement_edit_coordonnees_bailleur_', $signalementId),
        ];
    }

    /**
     * @return array<string>
     */
    private function getPayloadInformationsBailleur(string $beneficiaireRsa, string $beneficiaireFsl, int $signalementId): array
    {
        return [
            'beneficiaireRsa' => $beneficiaireRsa,
            'beneficiaireFsl' => $beneficiaireFsl,
            'revenuFiscal' => '',
            'dateNaissance' => '',
            '_token' => $this->getCsrfToken('signalement_edit_informations_bailleur_', $signalementId),
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadCoordonneesFoyer(): array
    {
        return [
            'civilite' => 'mme',
            'nom' => 'Monfort',
            'prenom' => 'Nelson',
            'mail' => 'nelson.monfort@yopmail.com',
            'telephone' => '+33240556677',
            'telephoneBis' => '0611451264',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadInformationLogement(): array
    {
        return [
            'nombrePersonnes' => '4',
            'compositionLogementEnfants' => 'oui',
            'compositionLogementNombreEnfants' => '2',
            'dateEntree' => '2020-12-01',
            'bailleurDateEffetBail' => '',
            'bailDpeBail' => 'oui',
            'bailDpeInvariant' => 'abcd12ef34',
            'bailDpeEtatDesLieux' => 'oui',
            'bailDpeDpe' => 'oui',
            'bailDpeClasseEnergetique' => 'F',
            'loyer' => '494',
            'loyersPayes' => 'oui',
            'anneeConstruction' => '1994',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadCompositionLogement(): array
    {
        return [
            'type' => 'maison',
            'typeLogementNatureAutrePrecision' => '',
            'typeCompositionLogement' => 'plusieurs_pieces',
            'superficie' => '55',
            'compositionLogementNbPieces' => '5',
            'nombreEtages' => '1',
            'etage' => 'RDC',
            'avecFenetre' => 'non',
            'typeLogementCommoditesPieceAVivre9m' => 'oui',
            'typeLogementCommoditesCuisine' => 'oui',
            'typeLogementCommoditesCuisineCollective' => 'oui',
            'typeLogementCommoditesSalleDeBain' => 'oui',
            'typeLogementCommoditesSalleDeBainCollective' => 'non',
            'typeLogementCommoditesWc' => 'oui',
            'typeLogementCommoditesWcCollective' => 'non',
            'typeLogementCommoditesWcCuisine' => 'non',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadSituationFoyer(): array
    {
        return [
            'isLogementSocial' => 'non',
            'isRelogement' => 'non',
            'isAllocataire' => 'non',
            'dateNaissanceOccupant' => '',
            'numAllocataire' => '702807',
            'logementSocialMontantAllocation' => '5000',
            'travailleurSocialQuitteLogement' => 'non',
            'travailleurSocialPreavisDepart' => 'non',
            'travailleurSocialAccompagnement' => 'non',
            'beneficiaireRsa' => 'non',
            'beneficiaireFsl' => 'non',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadProcedureDemarches(): array
    {
        return [
            'isProprioAverti' => '1',
            'infoProcedureBailMoyen' => 'courrier',
            'infoProcedureBailDate' => '11/2024',
            'infoProcedureBailReponse' => 'Réponse du bailleur',
            'infoProcedureBailNumero' => 'R-TR45',
            'infoProcedureAssuranceContactee' => 'oui',
            'infoProcedureReponseAssurance' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'infoProcedureDepartApresTravaux' => 'oui',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadConsommationEnergetique(): array
    {
        return [
            'dateEntree' => '2021-05-01',
            'dpe' => 'oui',
            'classeEnergetique' => 'F',
            'dateDernierDPE' => '2023-01-02',
            'consommationEnergie' => '300',
            'superficie' => '100',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadAddress(): array
    {
        return [
            'adresse' => '17 Boulevard saade - quai joliette',
            'codePostal' => '13002',
            'ville' => 'Marseille',
            'needResetInsee' => '0',
            'manual' => '0',
            'insee' => '13202',
            'geolocLat' => '43.301787',
            'geolocLng' => '5.364626',
            'etage' => 'RDC',
            'escalier' => '5',
            'numAppart' => '369',
            'autre' => 'Les essentielles',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadCoordonneesTiers(): array
    {
        return [
            'nom' => 'Quatorze',
            'prenom' => 'Louis',
            'mail' => 'louis.quatorze@gmail.com',
            'telephone' => '0711554845',
            'lien' => 'PRO',
            'structure' => 'SCPI La fourragère',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadCoordonneesAgence(): array
    {
        return [
            'denomination' => 'Agence du Vieux Port',
            'nom' => 'Martin',
            'prenom' => 'Claire',
            'mail' => 'contact@agence-vieux-port.fr',
            'telephone' => '0491000000',
            'adresse' => '1 quai du Port',
            'codePostal' => '13002',
            'ville' => 'Marseille',
        ];
    }

    /**
     * @return array<string>
     */
    private static function getStaticPayloadCoordonneesSyndic(): array
    {
        return [
            'denomination' => 'Syndic de la Canebière',
            'nom' => 'Durand',
            'mail' => 'contact@syndic-canebiere.fr',
            'telephone' => '0491000001',
        ];
    }

    public static function provideEditSignalementRoutes(): \Generator
    {
        yield 'Edition Adresse logement' => [
            'back_signalement_edit_address',
            self::getStaticPayloadAddress(),
            'signalement_edit_address_',
        ];

        yield 'Edition Coordonnées du foyer' => [
            'back_signalement_edit_coordonnees_foyer',
            self::getStaticPayloadCoordonneesFoyer(),
            'signalement_edit_coordonnees_foyer_',
        ];

        yield 'Edition Coordonnées Tiers' => [
            'back_signalement_edit_coordonnees_tiers',
            self::getStaticPayloadCoordonneesTiers(),
            'signalement_edit_coordonnees_tiers_',
        ];

        yield 'Edition Coordonnées Agence' => [
            'back_signalement_edit_coordonnees_agence',
            self::getStaticPayloadCoordonneesAgence(),
            'signalement_edit_coordonnees_agence_',
        ];

        yield 'Edition Coordonnées Syndic' => [
            'back_signalement_edit_coordonnees_syndic',
            self::getStaticPayloadCoordonneesSyndic(),
            'signalement_edit_coordonnees_syndic_',
        ];

        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce cas
        yield 'Edition Informations sur le logement (ancien onglet)' => [
            'back_signalement_edit_informations_logement',
            self::getStaticPayloadInformationLogement(),
            'signalement_edit_informations_logement_',
        ];
        yield 'Edition Occupation du logement' => [
            'back_signalement_edit_occupation_logement',
            array_diff_key(self::getStaticPayloadInformationLogement(), ['bailDpeDpe' => null, 'bailDpeClasseEnergetique' => null]),
            'signalement_edit_occupation_logement_',
        ];

        yield 'Edition Description du logement' => [
            'back_signalement_edit_composition_logement',
            self::getStaticPayloadCompositionLogement(),
            'signalement_edit_composition_logement_',
        ];
        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce cas
        yield 'Edition Situation du foyer (old)' => [
            'back_signalement_edit_situation_foyer_old',
            self::getStaticPayloadSituationFoyer(),
            'signalement_edit_situation_foyer_old_',
        ];
        yield 'Edition Situation du foyer' => [
            'back_signalement_edit_situation_foyer',
            [...self::getStaticPayloadSituationFoyer(), 'infoProcedureDepartApresTravaux' => 'non'],
            'signalement_edit_situation_foyer_',
        ];

        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce cas
        yield 'Edition Procédure et démarches (old)' => [
            'back_signalement_edit_procedure_demarches_old',
            self::getStaticPayloadProcedureDemarches(),
            'signalement_edit_procedure_demarches_old_',
        ];
        yield 'Edition Procédure et démarches' => [
            'back_signalement_edit_procedure_demarches',
            array_diff_key(self::getStaticPayloadProcedureDemarches(), ['infoProcedureDepartApresTravaux' => null]),
            'signalement_edit_procedure_demarches_',
        ];
    }

    private function getCsrfToken(string $tokenId, int $signalementId): string
    {
        return $this->generateCsrfToken($this->client, $tokenId.$signalementId);
    }

    /**
     * @param array<string, string> $formData
     */
    #[DataProvider('provideEditAddressData')]
    public function testEditAddress(
        string $signalementReference,
        array $formData,
        string $expectedMessage,
    ): void {
        $signalement = $this->signalementRepository->findOneBy(['reference' => $signalementReference]);
        $route = $this->router->generate('back_signalement_edit_address', ['uuid' => $signalement->getUuid()]);

        $payload = array_merge($formData, ['_token' => $this->generateCsrfToken($this->client, 'signalement_edit_address_'.$signalement->getId())]);

        $this->client->request('POST', $route, [], [], [], (string) json_encode($payload));
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertEquals($expectedMessage, $response['flashMessages'][0]['message']);
    }

    public static function provideEditAddressData(): \Generator
    {
        yield 'Bouches-du-Rhône: peut mettre à jour avec un autre code postal/insee du 13' => [
            'signalementReference' => '2022-1',
            'formData' => [
                'adresse' => '3 Rue Mars',
                'codePostal' => '13015',
                'ville' => 'Marseille',
                'insee' => '13203',
            ],
            'expectedMessage' => 'L\'adresse du logement a bien été modifiée.',
        ];

        yield 'Bouches-du-Rhône: ne peut pas mettre à jour avec un autre code postal autre territoire mais insee du 13' => [
            'signalementReference' => '2022-1',
            'formData' => [
                'adresse' => '3 Rue Mars',
                'codePostal' => '14004',
                'ville' => 'Marseille',
                'insee' => '13203',
            ],
            'expectedMessage' => 'Commune Marseille introuvable pour le code postal 14004',
        ];

        yield 'Bouches-du-Rhône: ne peut pas mettre à jour avec le code postal/insee d\'un autre territoire' => [
            'signalementReference' => '2022-1',
            'formData' => [
                'adresse' => '3 Rue Mars',
                'codePostal' => '01170',
                'ville' => 'Gex',
                'insee' => '01173',
            ],
            'expectedMessage' => 'Le territoire calculé (Ain) pour l\'adresse ne correspond pas au territoire attendu (Bouches-du-Rhône).',
        ];

        yield 'Rhône: ne peut pas mettre à jour avec le code postal/insee de la Métropole de Lyon' => [
            'signalementReference' => '2023-2',
            'formData' => [
                'adresse' => 'Le Bourg 47 route du Montmeterme',
                'codePostal' => '69001',
                'ville' => 'Lyon',
                'insee' => '69381',
            ],
            'expectedMessage' => 'Le territoire calculé (Métropole de Lyon) pour l\'adresse ne correspond pas au territoire attendu (Rhône).',
        ];

        yield 'Finistère: ne peut pas mettre à jour avec le code postal/insee d\'une commune non autorisée' => [
            'signalementReference' => '2023-24',
            'formData' => [
                'adresse' => '4 Rue Auguste Gache',
                'codePostal' => '29100',
                'ville' => 'Douarnenez',
                'insee' => '29046',
            ],
            'expectedMessage' => 'Le territoire n\'est pas actif pour le code insee 29046.',
        ];

        yield 'Finistère: peut mettre à jour de Quimper vers Brest' => [
            'signalementReference' => '2023-24',
            'formData' => [
                'adresse' => '4 Rue Auguste Gache',
                'codePostal' => '29200',
                'ville' => 'Brest',
                'insee' => '29019',
            ],
            'expectedMessage' => 'L\'adresse du logement a bien été modifiée.',
        ];
    }
}
