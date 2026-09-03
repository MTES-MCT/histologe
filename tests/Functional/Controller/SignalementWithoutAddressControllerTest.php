<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchSignalementWithoutAddress;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementWithoutAddressControllerTest extends WebTestCase
{
    use SessionHelper;

    private const string PARIS_ADDRESS_LABEL = '10 Rue de la Paix';
    private const string PARIS_POSTAL_CODE = '75002';
    private const string PARIS_INSEE_CODE = '75102';
    private const string PARIS_BAN_ID = '75102_7060_00010';

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;
    private SignalementRepository $signalementRepository;
    private TerritoryRepository $territoryRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testSignalementWithoutAddressIndex(): void
    {
        $count = $this->signalementRepository->count(['address' => null]);

        $route = $this->router->generate('back_signalement_without_address_index');
        $this->client->request('GET', $route);
        $this->assertResponseIsSuccessful();

        $expectedLabel = $count > 1 ? 'signalements sans adresse trouvés' : 'signalement sans adresse trouvé';
        $this->assertSelectorTextContains('h2#desc-table', $count.' '.$expectedLabel);
    }

    public function testBothActionButtonsShowWhenTerritoryAndCpInseeAreBothInconsistent(): void
    {
        $signalement = $this->getSignalementWithoutAddress();
        $loireAtlantique = $this->territoryRepository->findOneBy(['zip' => '44']);

        // CP et INSEE valides individuellement mais incohérents entre eux (aucune commune 44/54 n'existe),
        // et le territoire assigné (Loire-Atlantique) ne correspond pas au territoire calculé depuis
        // l'INSEE (54 - Meurthe-et-Moselle) : les deux anomalies, et donc les deux actions, doivent apparaître.
        $this->setDeprecatedOccupantFields($signalement, cpOccupant: '44420', inseeOccupant: '54570', territory: $loireAtlantique);

        $route = $this->router->generate('back_signalement_without_address_index', ['territory' => $loireAtlantique->getId()]);
        $crawler = $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $row = $crawler->filter('tr:contains("#'.$signalement->getReference().'")');
        $this->assertCount(1, $row->filter('.btn-change-territory'));
        $this->assertCount(1, $row->filter('.btn-search-address'));
    }

    public function testSearchAddressButtonShowsEvenWithoutAnyError(): void
    {
        $signalement = $this->getSignalementWithoutAddress();
        $corseDuSud = $this->territoryRepository->findOneBy(['zip' => '2A']);

        // CP et INSEE valides et cohérents entre eux (Ajaccio), territoire assigné cohérent avec le
        // calcul : aucune anomalie. Le signalement n'a toujours pas d'Address liée, donc le bouton de
        // recherche doit rester disponible pour permettre de le lier.
        $this->setDeprecatedOccupantFields($signalement, cpOccupant: '20000', inseeOccupant: '2A004', territory: $corseDuSud);

        $route = $this->router->generate('back_signalement_without_address_index', ['territory' => $corseDuSud->getId()]);
        $crawler = $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $row = $crawler->filter('tr:contains("#'.$signalement->getReference().'")');
        $this->assertCount(0, $row->filter('.btn-change-territory'));
        $this->assertCount(1, $row->filter('.btn-search-address'));
    }

    public function testBulkLinkButtonPreviewUrlIncludesCurrentPage(): void
    {
        $route = $this->router->generate('back_signalement_without_address_index', ['page' => 2]);
        $crawler = $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $previewUrl = $crawler->filter('.btn-bulk-link-address')->attr('data-preview-url');
        $this->assertStringContainsString('page=2', (string) $previewUrl);
    }

    public function testSignalementWithoutAddressIndexIsRestrictedToAdmin(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_without_address_index');
        $this->client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSearchAddress(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $route = $this->router->generate('back_signalement_without_address_search', [
            'uuid' => $signalement->getUuid(),
        ]);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertSame(self::PARIS_ADDRESS_LABEL.' '.self::PARIS_POSTAL_CODE.' Paris', $responseData['query']);
        $this->assertCount(1, $responseData['results']);
        $this->assertSame(self::PARIS_INSEE_CODE, $responseData['results'][0]['properties']['citycode']);
    }

    public function testSearchAddressExcludesMunicipalityOnlyResults(): void
    {
        // un résultat de type "municipality" (juste une ville, sans rue) ne peut pas être lié :
        // AddressFactory exige une rue, il ne doit donc pas être proposé.
        $signalement = $this->getSignalementWithoutAddress();
        $this->setDeprecatedOccupantFields($signalement, adresseOccupant: 'Place Sans Rue', cpOccupant: '', villeOccupant: 'Testville', inseeOccupant: null, territory: null);

        $route = $this->router->generate('back_signalement_without_address_search', [
            'uuid' => $signalement->getUuid(),
        ]);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertSame([], $responseData['results']);
    }

    public function testLinkAddress(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $route = $this->router->generate('back_signalement_without_address_link', [
            'uuid' => $signalement->getUuid(),
        ]);

        $feature = $this->parisBanFeature();

        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_link_address_'.$signalement->getId()),
            'feature' => json_encode($feature),
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['closeModal']);

        $this->assertNotNull($signalement->getAddress());
        $this->assertSame(self::PARIS_POSTAL_CODE, $signalement->getAddress()->getPostCode());
        $this->assertSame(self::PARIS_INSEE_CODE, $signalement->getAddress()->getCityCode());
        $this->assertSame(self::PARIS_BAN_ID, $signalement->getAddress()->getBanId());
    }

    public function testLinkAddressWithInvalidCsrfToken(): void
    {
        $signalement = $this->getSignalementWithoutAddress();

        $route = $this->router->generate('back_signalement_without_address_link', [
            'uuid' => $signalement->getUuid(),
        ]);

        $this->client->request('POST', $route, [
            '_token' => 'invalid-token',
            'feature' => json_encode(['properties' => ['postcode' => self::PARIS_POSTAL_CODE, 'citycode' => self::PARIS_INSEE_CODE]]),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSignalementHasNoAddress($signalement);
    }

    public function testExport(): void
    {
        $route = $this->router->generate('back_signalement_without_address_export');
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="signalements-sans-adresse_', (string) $response->headers->get('Content-Disposition'));
    }

    public function testExportIsRestrictedToAdmin(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_without_address_export');
        $this->client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBulkLinkAddressPreview(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $route = $this->router->generate('back_signalement_without_address_bulk_link_preview', [
            'territory' => $signalement->getTerritoryDeprecated()?->getId(),
        ]);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);

        // au moins notre candidat ; d'autres signalements de fixtures déjà valides et assignés
        // au même territoire peuvent aussi être éligibles (la condition n'exclut que
        // INCONSISTENT_TERRITORY, pas seulement MISSING_CP_AND_INSEE), donc pas de count exact ici.
        $this->assertGreaterThanOrEqual(1, $responseData['count']);
        $this->assertStringContainsString($signalement->getReference(), $responseData['html']);
        $this->assertStringContainsString(self::PARIS_ADDRESS_LABEL.' '.self::PARIS_POSTAL_CODE.' Paris', $responseData['html']);
    }

    public function testBulkLinkAddressPreviewExcludesMunicipalityOnlyResults(): void
    {
        // même règle côté lien en masse : un signalement dont le seul résultat BAN est de type
        // "municipality" (pas de rue) ne doit pas apparaître comme candidat.
        $signalement = $this->getSignalementWithoutAddress();
        $this->setDeprecatedOccupantFields($signalement, adresseOccupant: 'Place Sans Rue', cpOccupant: '', villeOccupant: 'Testville', inseeOccupant: null, territory: null);

        $route = $this->router->generate('back_signalement_without_address_bulk_link_preview');
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertStringNotContainsString('#'.$signalement->getReference(), $responseData['html']);
    }

    public function testBulkLinkAddress(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $candidates = [[
            'uuid' => $signalement->getUuid(),
            'feature' => $this->parisBanFeature(),
        ]];

        // Note : on filtre ici par statut (et non territoire) car TerritoryChoiceType ne liste que les
        // territoires actifs ; soumettre un territoire inactif via search_params rendrait le formulaire
        // invalide et réinitialiserait tous les filtres, ce qui n'est pas ce qu'on veut isoler ici.
        $searchParams = http_build_query(['statut' => SignalementStatus::ACTIVE->value]);

        $route = $this->router->generate('back_signalement_without_address_bulk_link');
        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_bulk_link_address'),
            'candidates' => json_encode($candidates),
            'search_params' => $searchParams,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['closeModal']);
        $this->assertStringContainsString('1 signalement', $responseData['flashMessages'][0]['message']);

        $this->assertNotNull($signalement->getAddress());
        $this->assertSame(self::PARIS_INSEE_CODE, $signalement->getAddress()->getCityCode());

        // le fragment de liste renvoyé doit conserver le filtre de statut (régression : search_params manquant côté panneau)
        $this->assertStringContainsString(
            'statut=ACTIVE',
            $responseData['htmlTargetContents'][0]['content']
        );
    }

    public function testBulkLinkAddressWithInvalidCsrfToken(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $candidates = [[
            'uuid' => $signalement->getUuid(),
            'feature' => ['properties' => ['postcode' => self::PARIS_POSTAL_CODE, 'citycode' => self::PARIS_INSEE_CODE]],
        ]];

        $route = $this->router->generate('back_signalement_without_address_bulk_link');
        $this->client->request('POST', $route, [
            '_token' => 'invalid-token',
            'candidates' => json_encode($candidates),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSignalementHasNoAddress($signalement);
    }

    public function testFindSignalementsWithoutAddressIgnoresPagination(): void
    {
        $signalement = $this->prepareSignalementForParisSearch();

        $search = new SearchSignalementWithoutAddress();
        $search->setTerritory($signalement->getTerritoryDeprecated());

        $results = $this->signalementRepository->findSignalementsWithoutAddress($search);

        $this->assertNotEmpty($results);
        $this->assertContainsEquals($signalement, $results);
        foreach ($results as $result) {
            $this->assertSame($signalement->getTerritoryDeprecated()?->getId(), $result->getTerritoryDeprecated()?->getId());
            $this->assertSignalementHasNoAddress($result);
        }
    }

    private function prepareSignalementForParisSearch(): Signalement
    {
        $signalement = $this->getSignalementWithoutAddress();
        $paris = $this->territoryRepository->findOneBy(['zip' => '75']);

        $this->setDeprecatedOccupantFields(
            $signalement,
            adresseOccupant: self::PARIS_ADDRESS_LABEL,
            cpOccupant: self::PARIS_POSTAL_CODE,
            villeOccupant: 'Paris',
            inseeOccupant: null,
            territory: $paris,
        );

        return $signalement;
    }

    /**
     * Récupère un signalement sans adresse liée pour les besoins du test, sans dépendre de fixtures
     * dédiées : si aucun n'existe déjà en base, on en détache un existant (couvert par la transaction
     * de test, donc sans impact sur les autres tests ni sur les fixtures).
     */
    private function getSignalementWithoutAddress(): Signalement
    {
        $signalement = $this->signalementRepository->findOneBy(['address' => null]);
        if (!$signalement instanceof Signalement) {
            $signalement = $this->signalementRepository->findOneBy([]);
            $this->assertInstanceOf(Signalement::class, $signalement, 'Aucun signalement en base pour ce test.');
            $signalement->setAddress(null);
            $this->entityManager->flush();
        }

        return $signalement;
    }

    /**
     * Les setters *Deprecated (adresse/cp/ville/insee/territory) ont été retirés de l'entité : ces
     * champs ne sont plus censés être modifiés que par les imports historiques. On écrit donc
     * directement en base pour reproduire un signalement legacy, puis on recharge l'entité pour que
     * les getters *Deprecated reflètent ces valeurs.
     */
    private function setDeprecatedOccupantFields(
        Signalement $signalement,
        ?string $adresseOccupant = null,
        ?string $cpOccupant = null,
        ?string $villeOccupant = null,
        ?string $inseeOccupant = null,
        ?Territory $territory = null,
    ): void {
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE signalement SET adresse_occupant = ?, cp_occupant = ?, ville_occupant = ?, insee_occupant = ?, territory_id = ? WHERE id = ?',
            [$adresseOccupant, $cpOccupant, $villeOccupant, $inseeOccupant, $territory?->getId(), $signalement->getId()]
        );
        $this->entityManager->refresh($signalement);
    }

    /**
     * getAddress() renvoie désormais toujours un objet Address (réel ou reconstitué à la volée depuis
     * les champs *Deprecated), donc on ne peut plus vérifier "pas d'adresse" avec assertNull(getAddress()).
     * On revient à la vérité de la base : la colonne address_id doit rester NULL.
     */
    private function assertSignalementHasNoAddress(Signalement $signalement): void
    {
        $this->assertNotNull($this->signalementRepository->findOneBy(['id' => $signalement->getId(), 'address' => null]));
    }

    /**
     * @return array<string, mixed>
     */
    private function parisBanFeature(): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'label' => self::PARIS_ADDRESS_LABEL.' '.self::PARIS_POSTAL_CODE.' Paris',
                'housenumber' => '10',
                'name' => self::PARIS_ADDRESS_LABEL,
                'street' => 'Rue de la Paix',
                'postcode' => self::PARIS_POSTAL_CODE,
                'citycode' => self::PARIS_INSEE_CODE,
                'city' => 'Paris',
                'id' => self::PARIS_BAN_ID,
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [2.330, 48.869],
            ],
        ];
    }
}
