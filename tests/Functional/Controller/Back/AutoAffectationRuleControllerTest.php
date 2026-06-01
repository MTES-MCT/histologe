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
    private const string CSV_HEADERS = "Territoire;Statut;Type de partenaire;Profil déclarant;Parc;Allocataire;Code insee inclus;Code insee exclus;Id partenaires exclus;Procédures suspectées;Actions\n";

    private ?KernelBrowser $client = null;
    private RouterInterface $router;
    private TerritoryRepository $territoryRepository;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->territoryRepository = static::getContainer()->get(TerritoryRepository::class);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::ADMIN_EMAIL]);
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
        $url = $this->router->generate('back_auto_affectation_rule_import');
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Importer des règles d\'auto-affectation');
    }

    public function testImportWithNoFileShowsError(): void
    {
        $url = $this->router->generate('back_auto_affectation_rule_import');
        $csrfToken = $this->generateCsrfToken($this->client, 'auto_affectation_rule_import');

        $this->client->request('POST', $url, ['_token' => $csrfToken], []);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.fr-alert--error', 'Veuillez sélectionner un fichier CSV');
    }

    public function testImportWithInvalidCsvShowsErrors(): void
    {
        $csvContent = self::CSV_HEADERS
            ."TERRITOIRE_INEXISTANT;ACTIVE;CAF / MSA;all;prive;caf;/;/;/;/;\n"
            ."34 - Hérault;STATUT_INVALIDE;CAF / MSA;all;prive;caf;/;/;/;/;\n";

        $uploadedFile = $this->createUploadedCsv($csvContent);
        $url = $this->router->generate('back_auto_affectation_rule_import');
        $csrfToken = $this->generateCsrfToken($this->client, 'auto_affectation_rule_import');

        $this->client->request('POST', $url, ['_token' => $csrfToken], ['csv_file' => $uploadedFile]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.fr-alert--error');
        $this->assertSelectorTextContains('.fr-alert--error', 'Ligne 2');
        $this->assertSelectorTextContains('.fr-alert--error', 'Ligne 3');
    }

    public function testImportWithValidSingleTerritoryRedirectsToFilteredList(): void
    {
        $territory = $this->territoryRepository->findOneBy(['zip' => '34', 'name' => 'Hérault']);
        $this->assertNotNull($territory);

        // These rules do not exist in fixtures
        $csvContent = self::CSV_HEADERS
            ."34 - Hérault;ACTIVE;CAF / MSA;all;prive;nsp;/;/;/;/;\n"
            ."34 - Hérault;ACTIVE;CCAS;all;all;all;/;/;/;/;\n";

        $uploadedFile = $this->createUploadedCsv($csvContent);
        $url = $this->router->generate('back_auto_affectation_rule_import');
        $csrfToken = $this->generateCsrfToken($this->client, 'auto_affectation_rule_import');

        $this->client->request('POST', $url, ['_token' => $csrfToken], ['csv_file' => $uploadedFile]);

        $this->assertResponseRedirects();
        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('territory='.$territory->getId(), $redirectUrl);
    }

    public function testImportWithMultipleTerritoriesRedirectsToUnfilteredList(): void
    {
        // Rules for two different territories
        $csvContent = self::CSV_HEADERS
            ."34 - Hérault;ACTIVE;CAF / MSA;all;prive;nsp;/;/;/;/;\n"
            ."13 - Bouches-du-Rhône;ACTIVE;DDT/M;all;all;all;/;/;/;/;\n";

        $uploadedFile = $this->createUploadedCsv($csvContent);
        $url = $this->router->generate('back_auto_affectation_rule_import');
        $csrfToken = $this->generateCsrfToken($this->client, 'auto_affectation_rule_import');

        $this->client->request('POST', $url, ['_token' => $csrfToken], ['csv_file' => $uploadedFile]);

        $this->assertResponseRedirects();
        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        $listUrl = $this->router->generate('back_auto_affectation_rule_index');
        $this->assertStringNotContainsString('territory=', $redirectUrl);
        $this->assertStringContainsString($listUrl, $redirectUrl);
    }

    public function testImportWithDuplicateRuleShowsConflictError(): void
    {
        // Exactly matches an existing fixture rule: Hérault / CAF_MSA / all / prive / caf
        $csvContent = self::CSV_HEADERS
            ."34 - Hérault;ACTIVE;CAF / MSA;all;prive;caf;/;/;/;/;\n";

        $uploadedFile = $this->createUploadedCsv($csvContent);
        $url = $this->router->generate('back_auto_affectation_rule_import');
        $csrfToken = $this->generateCsrfToken($this->client, 'auto_affectation_rule_import');

        $this->client->request('POST', $url, ['_token' => $csrfToken], ['csv_file' => $uploadedFile]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.fr-alert--error');
        $this->assertSelectorTextContains('.fr-alert--error', 'règle identique existe déjà');
    }

    public function testImportWithInvalidCsrfTokenShowsError(): void
    {
        $csvContent = self::CSV_HEADERS.'34 - Hérault;ACTIVE;CCAS;all;all;nsp;/;/;/;/;';
        $uploadedFile = $this->createUploadedCsv($csvContent);
        $url = $this->router->generate('back_auto_affectation_rule_import');

        $this->client->request('POST', $url, ['_token' => 'invalid_token'], ['csv_file' => $uploadedFile]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.fr-alert--error');
    }

    private function createUploadedCsv(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test_auto_affectation_rule_');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'import.csv', 'text/csv', null, true);
    }
}
