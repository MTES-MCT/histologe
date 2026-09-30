<?php

namespace App\Tests\Functional\Controller;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class AutoAffectationRuleControllerTest extends WebTestCase
{
    use SessionHelper;

    private const int DEPT_93_ID = 95;
    private const int DEPT_44_ID = 45;
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private AutoAffectationRuleRepository $autoAffectationRuleRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->autoAffectationRuleRepository = static::getContainer()->get(AutoAffectationRuleRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testAutoAffectationRulesSuccessfullyDisplay(): void
    {
        $route = $this->router->generate('back_auto_affectation_rule_index');
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
    }

    public function testAutoAffectationRuleFormSubmit(): void
    {
        $route = $this->router->generate('back_auto_affectation_rule_new');
        $this->client->request('GET', $route);

        $this->client->submitForm(
            'Créer la règle d\'auto-affectation',
            [
                'auto_affectation_rule[territory]' => 1,
                'auto_affectation_rule[partnerType]' => PartnerType::ARS->value,
                'auto_affectation_rule[profileDeclarant]' => 'occupant',
                'auto_affectation_rule[parc]' => 'all',
                'auto_affectation_rule[allocataire]' => 'oui',
                'auto_affectation_rule[accompagnementTravailleurSocial]' => 'oui',
                'auto_affectation_rule[demandeLogementSocial]' => 'nsp',
                'auto_affectation_rule[inseeToInclude]' => '',
                'auto_affectation_rule[inseeToExclude]' => '',
                'auto_affectation_rule[partnerToExclude]' => '',
            ]
        );

        $this->assertResponseRedirects('/bo/auto-affectation/');
    }

    public function testAutoAffectationRuleEditFormSubmit(): void
    {
        /** @var AutoAffectationRule $autoAffectationRule */
        $autoAffectationRule = $this->autoAffectationRuleRepository->findOneBy(['territory' => self::DEPT_93_ID]);

        $route = $this->router->generate('back_auto_affectation_rule_edit', ['id' => $autoAffectationRule->getId()]);
        $this->client->request('GET', $route);

        $this->client->submitForm(
            'Enregistrer',
            [
                'auto_affectation_rule[territory]' => self::DEPT_93_ID,
                'auto_affectation_rule[partnerType]' => PartnerType::ARS->value,
                'auto_affectation_rule[profileDeclarant]' => 'occupant',
                'auto_affectation_rule[parc]' => 'all',
                'auto_affectation_rule[allocataire]' => 'oui',
                'auto_affectation_rule[accompagnementTravailleurSocial]' => 'oui',
                'auto_affectation_rule[demandeLogementSocial]' => 'nsp',
                'auto_affectation_rule[inseeToInclude]' => '',
                'auto_affectation_rule[inseeToExclude]' => '',
                'auto_affectation_rule[partnerToExclude]' => '',
            ]
        );

        $this->assertResponseRedirects('/bo/auto-affectation/');
    }

    public function testAutoAffectationRuleEditFormSubmitWithZones(): void
    {
        $zoneRepository = static::getContainer()->get(ZoneRepository::class);
        $zoneToIncludeId = (string) $zoneRepository->findOneBy(['name' => 'La Bodinière'])->getId();
        $zoneToExcludeId = (string) $zoneRepository->findOneBy(['name' => 'StMars'])->getId();
        /** @var AutoAffectationRule $autoAffectationRule */
        $autoAffectationRule = $this->autoAffectationRuleRepository->findOneBy(['territory' => self::DEPT_44_ID, 'status' => AutoAffectationRule::STATUS_ACTIVE]);

        $route = $this->router->generate('back_auto_affectation_rule_edit', ['id' => $autoAffectationRule->getId()]);
        $this->client->request('GET', $route);
        $this->client->submitForm(
            'Enregistrer',
            [
                'auto_affectation_rule[zoneToInclude]' => $zoneToIncludeId,
                'auto_affectation_rule[zoneToExclude]' => ' '.$zoneToExcludeId.' , ',
            ]
        );

        $this->assertResponseRedirects('/bo/auto-affectation/');
        $autoAffectationRule = $this->autoAffectationRuleRepository->find($autoAffectationRule->getId());
        $this->assertSame([$zoneToIncludeId], $autoAffectationRule->getZoneToInclude());
        $this->assertSame([$zoneToExcludeId], $autoAffectationRule->getZoneToExclude());

        // les zones enregistrées sont réaffichées dans le formulaire
        $crawler = $this->client->request('GET', $route);
        $this->assertSame($zoneToIncludeId, $crawler->filter('#auto_affectation_rule_zoneToInclude')->attr('value'));
        $this->assertSame($zoneToExcludeId, $crawler->filter('#auto_affectation_rule_zoneToExclude')->attr('value'));
    }

    public function testAutoAffectationRuleEditFormSubmitWithInvalidZones(): void
    {
        $zoneRepository = static::getContainer()->get(ZoneRepository::class);
        $zoneOfAnotherTerritoryId = (string) $zoneRepository->findOneBy(['name' => 'Permis louer Agde'])->getId();
        $zoneId = (string) $zoneRepository->findOneBy(['name' => 'StMars'])->getId();
        /** @var AutoAffectationRule $autoAffectationRule */
        $autoAffectationRule = $this->autoAffectationRuleRepository->findOneBy(['territory' => self::DEPT_44_ID, 'status' => AutoAffectationRule::STATUS_ACTIVE]);

        $route = $this->router->generate('back_auto_affectation_rule_edit', ['id' => $autoAffectationRule->getId()]);
        $this->client->request('GET', $route);
        $this->client->submitForm(
            'Enregistrer',
            [
                'auto_affectation_rule[zoneToInclude]' => $zoneOfAnotherTerritoryId.',999999,'.$zoneId,
                'auto_affectation_rule[zoneToExclude]' => $zoneId,
            ]
        );

        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('La zone ID '.$zoneOfAnotherTerritoryId.' n&#039;appartient pas au territoire', $content);
        $this->assertStringContainsString('La zone ID 999999 est introuvable.', $content);
        $this->assertStringContainsString('La zone ID '.$zoneId.' ne peut pas être à la fois incluse et exclue.', $content);
    }

    public function testAutoAffectationRuleEditFormSubmitWithInvalidPartnerToExclude(): void
    {
        /** @var AutoAffectationRule $autoAffectationRule */
        $autoAffectationRule = $this->autoAffectationRuleRepository->findOneBy(['territory' => self::DEPT_44_ID, 'status' => AutoAffectationRule::STATUS_ACTIVE]);

        $route = $this->router->generate('back_auto_affectation_rule_edit', ['id' => $autoAffectationRule->getId()]);
        $this->client->request('GET', $route);
        $this->client->submitForm('Enregistrer', ['auto_affectation_rule[partnerToExclude]' => '999999']);

        $this->assertStringContainsString('Le partenaire ID 999999 est introuvable.', (string) $this->client->getResponse()->getContent());
    }

    public function testDeleteAutoAffectationRule(): void
    {
        /** @var AutoAffectationRule $autoAffectationRule */
        $autoAffectationRule = $this->autoAffectationRuleRepository->findOneBy(['territory' => self::DEPT_93_ID]);

        $route = $this->router->generate('back_auto_affectation_rule_delete');
        $this->client->request(
            'POST',
            $route,
            [
                'autoaffectationrule_id' => $autoAffectationRule->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'autoaffectationrule_delete'),
            ]
        );

        $this->assertEquals(AutoAffectationRule::STATUS_ARCHIVED, $autoAffectationRule->getStatus());
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'La règle a bien été archivée.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testReactiveAutoAffectationRule(): void
    {
        /** @var AutoAffectationRule $autoAffectationRule */
        $autoAffectationRule = $this->autoAffectationRuleRepository->findOneBy(['territory' => 45]);
        $this->assertEquals(AutoAffectationRule::STATUS_ARCHIVED, $autoAffectationRule->getStatus());

        $route = $this->router->generate('back_auto_affectation_rule_reactive', ['id' => $autoAffectationRule->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'id' => $autoAffectationRule->getId(),
            ]
        );

        $this->assertEquals(AutoAffectationRule::STATUS_ACTIVE, $autoAffectationRule->getStatus());
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $msgFlash = 'La règle a bien été réactivée.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }
}
