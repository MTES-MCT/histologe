<?php

namespace App\Tests\Functional\Controller;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class AutoAffectationRuleControllerTest extends WebTestCase
{
    use SessionHelper;

    private const int DEPT_93_ID = 95;
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private AutoAffectationRuleRepository $autoAffectationRuleRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->autoAffectationRuleRepository = static::getContainer()->get(AutoAffectationRuleRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testAutoAffectationRulesSuccessfullyDisplay()
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
                'auto_affectation_rule[inseeToInclude]' => '',
                'auto_affectation_rule[inseeToExclude]' => '',
                'auto_affectation_rule[partnerToExclude]' => '',
            ]
        );

        $this->assertResponseRedirects('/bo/auto-affectation/');
    }

    public function testDeleteAutoAffectationRule()
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

        $this->assertResponseRedirects('/bo/auto-affectation/');
        $this->assertEquals(AutoAffectationRule::STATUS_ARCHIVED, $autoAffectationRule->getStatus());
    }

    public function testReactiveAutoAffectationRule()
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

        $this->assertResponseRedirects('/bo/auto-affectation/');
        $this->assertEquals(AutoAffectationRule::STATUS_ACTIVE, $autoAffectationRule->getStatus());
    }
}
