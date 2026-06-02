<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

class AutoAffectationRuleControllerTest extends WebTestCase
{
    use SessionHelper;

    private const string ADMIN_EMAIL = 'admin-01@signal-logement.fr';
    private const string CSV_HEADERS = "Statut;Type de partenaire;Profil déclarant;Parc;Allocataire;Code insee inclus;Code insee exclus;Id partenaires exclus;Procédures suspectées;Actions\n";
    private const string CSS_SELECTOR_ERROR = '.fr-alert--error';
    private const string CSRF_TOKEN_ID = 'auto_affectation_rule_import';

    private ?KernelBrowser $client = null;
    private RouterInterface $router;
    private TerritoryRepository $territoryRepository;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->territoryRepository = static::getContainer()->get(TerritoryRepository::class);

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => self::ADMIN_EMAIL]);
        $this->client->loginUser($user);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach (glob(sys_get_temp_dir().'/test_auto_affectation_rule_*') ?: [] as $file) {
            @unlink($file);
        }
    }

    public function testImportPageLoadsForAdmin(): void
    {
        $this->client->request('GET', $this->router->generate('back_auto_affectation_rule_import'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Importer des règles d\'auto-affectation');
        $this->assertSelectorExists('select[name="auto_affectation_rule_import[territory]"]');
    }

    public function testImportWithNoFileShowsError(): void
    {
        $territory = $this->territoryRepository->findOneBy(['zip' => '34', 'name' => 'Hérault']);

        $this->client->request('POST',
            $this->router->generate('back_auto_affectation_rule_import'),
            $this->buildPostParams($territory->getId()),
            [],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains(self::CSS_SELECTOR_ERROR, 'Veuillez sélectionner un fichier CSV');
    }

    public function testImportWithNoTerritoryShowsFormError(): void
    {
        $uploadedFile = $this->createUploadedCsv(self::CSV_HEADERS.'ACTIVE;CAF / MSA;all;prive;nsp;/;/;/;/;');

        $this->client->request('POST',
            $this->router->generate('back_auto_affectation_rule_import'),
            $this->buildPostParams(null),
            ['auto_affectation_rule_import' => ['csvFile' => $uploadedFile]],
        );

        $this->assertResponseIsSuccessful();
        // Form validation error for territory displayed by Symfony form
        $this->assertSelectorExists('.fr-error-text');
    }

    public function testImportWithInvalidCsvShowsErrors(): void
    {
        $territory = $this->territoryRepository->findOneBy(['zip' => '34', 'name' => 'Hérault']);
        $csvContent = self::CSV_HEADERS
            ."STATUT_INVALIDE;CAF / MSA;all;prive;caf;/;/;/;/;\n"
            ."ACTIVE;TYPE INVALIDE;all;prive;caf;/;/;/;/;\n";

        $uploadedFile = $this->createUploadedCsv($csvContent);

        $this->client->request('POST',
            $this->router->generate('back_auto_affectation_rule_import'),
            $this->buildPostParams($territory->getId()),
            ['auto_affectation_rule_import' => ['csvFile' => $uploadedFile]],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(self::CSS_SELECTOR_ERROR);
        $this->assertSelectorTextContains(self::CSS_SELECTOR_ERROR, 'Ligne 2');
        $this->assertSelectorTextContains(self::CSS_SELECTOR_ERROR, 'Ligne 3');
    }

    public function testImportWithValidCsvRedirectsToTerritoryFilteredList(): void
    {
        $territory = $this->territoryRepository->findOneBy(['zip' => '34', 'name' => 'Hérault']);
        // These rules do not exist in fixtures
        $csvContent = self::CSV_HEADERS
            ."ACTIVE;CAF / MSA;all;prive;nsp;/;/;/;/;\n"
            ."ACTIVE;CCAS;all;all;all;/;/;/;/;\n";

        $uploadedFile = $this->createUploadedCsv($csvContent);

        $this->client->request('POST',
            $this->router->generate('back_auto_affectation_rule_import'),
            $this->buildPostParams($territory->getId()),
            ['auto_affectation_rule_import' => ['csvFile' => $uploadedFile]],
        );

        $this->assertResponseRedirects();
        $this->assertStringContainsString(
            'territory='.$territory->getId(),
            $this->client->getResponse()->headers->get('Location'),
        );
    }

    public function testImportWithDuplicateRuleShowsConflictError(): void
    {
        $territory = $this->territoryRepository->findOneBy(['zip' => '34', 'name' => 'Hérault']);
        // Exactly matches an existing fixture rule: Hérault / CAF_MSA / all / prive / caf
        $csvContent = self::CSV_HEADERS.'ACTIVE;CAF / MSA;all;prive;caf;/;/;/;/;';

        $uploadedFile = $this->createUploadedCsv($csvContent);

        $this->client->request('POST',
            $this->router->generate('back_auto_affectation_rule_import'),
            $this->buildPostParams($territory->getId()),
            ['auto_affectation_rule_import' => ['csvFile' => $uploadedFile]],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(self::CSS_SELECTOR_ERROR);
        $this->assertSelectorTextContains(self::CSS_SELECTOR_ERROR, 'règle identique existe déjà');
    }

    public function testImportWithInvalidCsrfTokenShowsError(): void
    {
        $territory = $this->territoryRepository->findOneBy(['zip' => '34', 'name' => 'Hérault']);
        $uploadedFile = $this->createUploadedCsv(self::CSV_HEADERS.'ACTIVE;CCAS;all;all;nsp;/;/;/;/;');

        $this->client->request('POST',
            $this->router->generate('back_auto_affectation_rule_import'),
            ['_token' => 'invalid_token', 'auto_affectation_rule_import' => ['territory' => $territory->getId()]],
            ['auto_affectation_rule_import' => ['csvFile' => $uploadedFile]],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(self::CSS_SELECTOR_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPostParams(?int $territoryId): array
    {
        return [
            '_token' => $this->generateCsrfToken($this->client, self::CSRF_TOKEN_ID),
            'auto_affectation_rule_import' => ['territory' => $territoryId],
        ];
    }

    private function createUploadedCsv(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test_auto_affectation_rule_');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'import.csv', 'text/csv', null, true);
    }
}
