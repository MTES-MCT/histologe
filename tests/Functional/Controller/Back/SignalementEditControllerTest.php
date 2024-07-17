<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementEditControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private RouterInterface $router;
    private ?Signalement $signalement = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /* @var UserRepository $userRepository */
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        /* @var SignalementRepository $signalementRepository */
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /* @var RouterInterface $router */
        $this->router = static::getContainer()->get(RouterInterface::class);
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);
        $this->signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
    }

    public function testEditCoordonneesBailleurWithBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy([
            'isLogementSocial' => true,
            'villeOccupant' => 'Marseille',
        ]);

        $route = $this->router->generate(
            'back_signalement_edit_coordonnees_bailleur',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = $this->getPayloadCoordonneesBailleur('13 habitat', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], json_encode($payload));

        $this->assertResponseIsSuccessful();

        $this->assertEquals('13 HABITAT', $signalement->getBailleur()->getName());
        $this->assertEquals('13 HABITAT', $signalement->getNomProprio());
    }

    public function testEditCoordonneesBailleurWithCustomBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
        $route = $this->router->generate(
            'back_signalement_edit_coordonnees_bailleur',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = $this->getPayloadCoordonneesBailleur('Habitat Social Solidaire', $signalement->getId());
        $this->client->request('POST', $route, [], [], [], json_encode($payload));

        $this->assertResponseIsSuccessful();
        $this->assertNull($signalement->getBailleur());
        $this->assertEquals('Habitat Social Solidaire', $signalement->getNomProprio());
    }

    /**
     * @dataProvider provideEditSignalementRoutes
     */
    public function testEditSignalementSuccess(string $routeName, array $payload, string $token): void
    {
        $route = $this->router->generate(
            $routeName,
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload['_token'] = $this->getCsrfToken($token, $this->signalement->getId());

        $this->client->request('POST', $route, [], [], [], json_encode($payload));

        $this->assertResponseIsSuccessful();
    }

    /**
     * @dataProvider provideEditSignalementRoutes
     */
    public function testEditSignalementUnauthorization(string $routeName, array $payload, string $token): void
    {
        $route = $this->router->generate(
            $routeName,
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload['_token'] = '1234';
        $this->client->request('POST', $route, [], [], [], json_encode($payload));

        $this->assertResponseStatusCodeSame(401, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider provideEditSignalementRoutes
     */
    public function testEditSignalementError(string $routeName, array $payload, string $token): void
    {
        $route = $this->router->generate(
            $routeName,
            ['uuid' => $this->signalement->getUuid()]
        );

        $payload['_token'] = $this->getCsrfToken($token, $this->signalement->getId());
        $payload[key($payload)] = str_repeat('x', 5000);
        $this->client->request('POST', $route, [], [], [], json_encode($payload));
        $this->assertResponseStatusCodeSame(400, $this->client->getResponse()->getStatusCode());
    }

    private function getPayloadCoordonneesBailleur(string $bailleurName, int $signalementId): array
    {
        return [
            'nom' => $bailleurName,
            'prenom' => '',
            'mail' => 'contact@13habitat.fr',
            'telephone' => '0611000000',
            'ville' => 'Marseille',
            'beneficiaireRsa' => '',
            'beneficiaireFsl' => '',
            'revenuFiscal' => '',
            'dateNaissance' => '',
            '_token' => $this->getCsrfToken('signalement_edit_coordonnees_bailleur_', $signalementId),
        ];
    }

    private function getPayloadCoordonneesFoyer(): array
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

    private function getPayloadInformationLogement(): array
    {
        return [
            'nombrePersonnes' => '4',
            'compositionLogementEnfants' => 'oui',
            'dateEntree' => '2020-12-01',
            'bailleurDateEffetBail' => '',
            'bailDpeBail' => 'oui',
            'bailDpeEtatDesLieux' => 'oui',
            'bailDpeDpe' => 'oui',
            'loyer' => '494',
            'loyersPayes' => 'oui',
            'anneeConstruction' => '1994',
        ];
    }

    private function getPayloadCompositionLogement(): array
    {
        return [
            'type' => 'maison',
            'typeLogementNatureAutrePrecision' => '',
            'typeCompositionLogement' => 'plusieurs_pieces',
            'superficie' => '55',
            'compositionLogementHauteur' => 'oui',
            'compositionLogementNbPieces' => '5',
            'nombreEtages' => '1',
            'typeLogementRdc' => 'non',
            'typeLogementDernierEtage' => 'non',
            'typeLogementSousCombleSansFenetre' => 'non',
            'typeLogementSousSolSansFenetre' => 'non',
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

    private function getPayloadSituationFoyer(): array
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

    private function getPayloadProcedureDemarches(): array
    {
        return [
            'isProprioAverti' => '1',
            'infoProcedureAssuranceContactee' => 'oui',
            'infoProcedureReponseAssurance' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'infoProcedureDepartApresTravaux' => 'oui',
        ];
    }

    private function getPayloadAddress(): array
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
                'etage' => '8',
                'escalier' => '5',
                'numAppart' => '369',
                'autre' => 'Les essentielles',
        ];
    }

    private function getPayloadCoordonneesTiers(): array
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

    public function provideEditSignalementRoutes(): \Generator
    {
        yield 'Edition Adresse logement' => [
            'back_signalement_edit_address',
            $this->getPayloadAddress(),
            'signalement_edit_address_',
        ];

        yield 'Edition Coordonnées du foyer' => [
            'back_signalement_edit_coordonnees_foyer',
            $this->getPayloadCoordonneesFoyer(),
            'signalement_edit_coordonnees_foyer_',
        ];

        yield 'Edition Coordonnées Tiers' => [
            'back_signalement_edit_coordonnees_tiers',
            $this->getPayloadCoordonneesTiers(),
            'signalement_edit_coordonnees_tiers_',
        ];

        yield 'Edition Informations sur le logement' => [
            'back_signalement_edit_informations_logement',
            $this->getPayloadInformationLogement(),
            'signalement_edit_informations_logement_',
        ];

        yield 'Edition Description du logement' => [
            'back_signalement_edit_composition_logement',
            $this->getPayloadCompositionLogement(),
            'signalement_edit_composition_logement_',
        ];
        yield 'Edition Situation du foyer' => [
            'back_signalement_edit_situation_foyer',
            $this->getPayloadSituationFoyer(),
            'signalement_edit_situation_foyer_',
        ];

        yield 'Edition Procédure et démarches' => [
            'back_signalement_edit_procedure_demarches',
            $this->getPayloadProcedureDemarches(),
            'signalement_edit_procedure_demarches_',
        ];
    }

    private function getCsrfToken(string $tokenId, int $signalementId): string
    {
        return $this->generateCsrfToken($this->client, $tokenId.$signalementId);
    }
}
