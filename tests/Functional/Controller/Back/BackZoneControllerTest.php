<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\AutoAffectationRule;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackZoneControllerTest extends WebTestCase
{
    use SessionHelper;

    /**
     * @param array<mixed> $params
     */
    #[DataProvider('provideParamsZoneList')]
    public function testZoneList(array $params, int $nb): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_territory_management_zone_index');
        $client->request('GET', $route, $params);

        $this->assertSelectorTextContains('h2#desc-table', $nb.' zone');
    }

    public static function provideParamsZoneList(): \Generator
    {
        yield 'Search without params' => [[], 3];
        yield 'Search with queryName agde' => [['queryName' => 'agde'], 1];
        yield 'Search with territory 13' => [['territory' => 13], 0];
    }

    public function testZoneShow(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var ZoneRepository $zoneRepository */
        $zoneRepository = static::getContainer()->get(ZoneRepository::class);
        $zones = $zoneRepository->findAll();

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_territory_management_zone_show', ['zone' => $zones[0]->getId()]);
        $client->request('GET', $route);

        $this->assertSelectorTextContains('.fr-badge', 'Partenaire Zone Agde');
        $this->assertSelectorTextContains('h2', '1 signalement dans la zone');
    }

    public function testZoneEdit(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var ZoneRepository $zoneRepository */
        $zoneRepository = static::getContainer()->get(ZoneRepository::class);
        $zones = $zoneRepository->findAll();
        $zone = $zones[0];

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_territory_management_zone_edit', ['zone' => $zone->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'zone_type');
        $client->request('POST', $route, [
            '_token' => $csrfToken,
            'name' => 'Zone Test',
            'partners' => [],
        ]);

        $this->assertEquals('Zone Test', $zone->getName());
        $this->assertCount(0, $zone->getPartners());
    }

    #[DataProvider('provideZoneUsage')]
    public function testZoneDeleteIsBlockedWhenZoneIsUsedInActiveRule(string $usage): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $zoneId = $this->getZoneId('La Bodinière');
        $otherZoneId = $this->getZoneId('StMars');
        $rule = $this->getRules(AutoAffectationRule::STATUS_ACTIVE)[0];
        if ('include' === $usage) {
            $rule->setZoneToInclude([(string) $otherZoneId, (string) $zoneId]);
        } else {
            $rule->setZoneToExclude([(string) $zoneId]);
        }
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $response = $this->requestZoneDelete($client, $zoneId);

        $this->assertSame('alert', $response['flashMessages'][0]['type']);
        $this->assertSame('Suppression impossible', $response['flashMessages'][0]['title']);
        $this->assertSame(
            'Cette zone ne peut pas être supprimée car elle est utilisée par une règle d\'auto-affectation active. Merci de contacter les administrateurs pour faire modifier cette règle avant de supprimer la zone.',
            $response['flashMessages'][0]['message']
        );
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $this->assertNotNull(static::getContainer()->get(ZoneRepository::class)->find($zoneId));
    }

    public static function provideZoneUsage(): \Generator
    {
        yield 'zone included' => ['include'];
        yield 'zone excluded' => ['exclude'];
    }

    public function testZoneDeleteCleansArchivedAutoAffectationRules(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $zoneId = $this->getZoneId('La Bodinière');
        $otherZoneId = $this->getZoneId('StMars');
        [$ruleWithTwoZones, $ruleWithZoneExcluded] = $this->getRules(AutoAffectationRule::STATUS_ACTIVE);
        $ruleWithOnlyZoneIncluded = $this->getRules(AutoAffectationRule::STATUS_ARCHIVED)[0];
        $ruleWithTwoZones->setStatus(AutoAffectationRule::STATUS_ARCHIVED)->setZoneToInclude([(string) $otherZoneId, (string) $zoneId]);
        $ruleWithZoneExcluded->setStatus(AutoAffectationRule::STATUS_ARCHIVED)->setZoneToExclude([(string) $zoneId]);
        $ruleWithOnlyZoneIncluded->setZoneToInclude([(string) $zoneId]);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $response = $this->requestZoneDelete($client, $zoneId);

        $this->assertSame('success', $response['flashMessages'][0]['type']);
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $this->assertNull(static::getContainer()->get(ZoneRepository::class)->find($zoneId));
        $this->assertSame([(string) $otherZoneId], $this->getRule($ruleWithTwoZones->getId())->getZoneToInclude());
        $this->assertNull($this->getRule($ruleWithZoneExcluded->getId())->getZoneToExclude());
        $this->assertNull($this->getRule($ruleWithOnlyZoneIncluded->getId())->getZoneToInclude());
    }

    public function testZoneListDisablesDeleteButtonWhenZoneIsUsedInActiveRule(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $zoneId = $this->getZoneId('La Bodinière');
        $this->getRules(AutoAffectationRule::STATUS_ACTIVE)[0]->setZoneToExclude([(string) $zoneId]);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']));
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $crawler = $client->request('GET', $router->generate('back_territory_management_zone_index'));

        $this->assertCount(1, $crawler->filter('button[aria-label="Supprimer la zone La Bodinière"][disabled]'));
        $this->assertSelectorTextSame(
            '#tooltip_zone_delete_'.$zoneId,
            'Cette zone ne peut pas être supprimée car elle est utilisée par une règle d\'auto-affectation active. Merci de contacter les administrateurs pour faire modifier cette règle avant de supprimer la zone.'
        );
        $this->assertCount(0, $crawler->filter('button[aria-label="Supprimer la zone StMars"][disabled]'));
    }

    /**
     * @return array<string, mixed>
     */
    private function requestZoneDelete(KernelBrowser $client, int $zoneId): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']));
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_zone_delete', ['zone' => $zoneId, '_token' => $this->generateCsrfToken($client, 'zone_delete')]);
        $client->request('POST', $route);

        return json_decode((string) $client->getResponse()->getContent(), true);
    }

    private function getZoneId(string $name): int
    {
        return static::getContainer()->get(ZoneRepository::class)->findOneBy(['name' => $name])->getId();
    }

    /**
     * @return array<int, AutoAffectationRule>
     */
    private function getRules(string $status): array
    {
        $territory = static::getContainer()->get(ZoneRepository::class)->findOneBy(['name' => 'La Bodinière'])->getTerritory();

        return static::getContainer()->get(AutoAffectationRuleRepository::class)->findBy(['territory' => $territory, 'status' => $status], ['id' => 'ASC']);
    }

    private function getRule(int $id): AutoAffectationRule
    {
        return static::getContainer()->get(AutoAffectationRuleRepository::class)->find($id);
    }
}
