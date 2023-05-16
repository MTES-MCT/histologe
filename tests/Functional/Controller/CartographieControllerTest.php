<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartographieControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    /**
     * @dataProvider provideUserEmail
     */
    public function testCartographieSuccessfullyOrRedirectWithoutError500(string $email): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_cartographie');
        $client->request('GET', $route, [
            'load_markers' => true,
        ]);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    public function provideUserEmail(): \Generator
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findAll();
        /** @var User $user */
        foreach ($users as $user) {
            if ($user->getTerritory()) {
                yield $user->getEmail() => [$user->getEmail()];
            }
        }
    }

    /**
     * @dataProvider provideFilterSearch
     */
    public function testCartographieWithFilter(string $email, string $filter, string|array $terms)
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_cartographie');
        $crawler = $client->request('POST', $route, [
            $filter => $terms,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function provideFilterSearch(): \Generator
    {
        yield 'Super Admin by statut Nouveau' => ['admin-01@histologe.fr', 'bo-filters-statuses', ['1']];
        yield 'Resp territoire by statut Nouveau' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-statuses', ['1']];
        yield 'Partenaire by statut Nouveau' => ['user-13-01@histologe.fr', 'bo-filters-statuses', ['1']];

        yield 'Super Admin by Parc public/prive' => ['admin-01@histologe.fr', 'bo-filters-housetypes', ['1']];
        yield 'Resp territoire by Parc public/prive' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-housetypes', ['1']];
        yield 'Partenaire by Parc public/prive' => ['user-13-01@histologe.fr', 'bo-filters-housetypes', ['1']];

        yield 'Super Admin by affectation closed' => ['admin-01@histologe.fr', 'bo-filters-closed_affectation', ['ONE_CLOSED']];
        yield 'Resp territoire by affectation closed' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-closed_affectation', ['ONE_CLOSED']];

        yield 'Super Admin by city' => ['admin-01@histologe.fr', 'bo-filters-cities', ['Marseille']];
        yield 'Resp territoire by city' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-cities', ['Marseille']];
        yield 'Partenaire by city' => ['user-13-01@histologe.fr', 'bo-filters-cities', ['Marseille']];

        yield 'Super Admin by partenaire' => ['admin-01@histologe.fr', 'bo-filters-partners', ['5']];
        yield 'Resp territoire by partenaire' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-partners', ['5']];

        yield 'Super Admin by critère' => ['admin-01@histologe.fr', 'bo-filters-criteres', ['17']];
        yield 'Resp territoire by critère' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-criteres', ['17']];
        yield 'Partenaire by critère' => ['user-13-01@histologe.fr', 'bo-filters-criteres', ['17']];

        yield 'Super Admin by territory' => ['admin-01@histologe.fr', 'bo-filters-territories', ['1']];

        yield 'Super Admin by Search Terms with Reference' => ['admin-01@histologe.fr', 'bo-filters-searchterms', '2022-1'];
        yield 'Resp territoire by Search Terms with cp Occupant' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-searchterms', '13003'];
        yield 'Partenaire by Search Terms with cp Occupant 13005' => ['user-13-01@histologe.fr', 'bo-filters-searchterms', '13005'];
        yield 'Partenaire by Search Terms with city Occupant' => ['user-13-01@histologe.fr', 'bo-filters-searchterms', 'Gex'];

        yield 'Super Admin by Tags' => ['admin-01@histologe.fr', 'bo-filters-tags', ['3']];
        yield 'Resp territoire by Tags' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-tags', ['3']];
        yield 'Partenaire by Tags' => ['user-13-01@histologe.fr', 'bo-filters-tags', ['3']];

        yield 'Super Admin by scores' => ['admin-01@histologe.fr', 'bo-filters-scores', ['on' => '3', 'off' => '20']];
        yield 'Resp territoire by scores' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-scores', ['on' => '3', 'off' => '20']];
        yield 'Partenaire by scores' => ['user-13-01@histologe.fr', 'bo-filters-scores', ['on' => '3', 'off' => '20']];

        yield 'Super Admin by allocs' => ['admin-01@histologe.fr', 'bo-filters-allocs', ['CAF']];
        yield 'Resp territoire by allocs' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-allocs', ['CAF']];
        yield 'Partenaire by allocs' => ['user-13-01@histologe.fr', 'bo-filters-allocs', ['CAF']];

        yield 'Super Admin by avant1949' => ['admin-01@histologe.fr', 'bo-filters-avant1949', ['1']];
        yield 'Resp territoire by avant1949' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-avant1949', ['1']];
        yield 'Partenaire by avant1949' => ['user-13-01@histologe.fr', 'bo-filters-avant1949', ['1']];

        yield 'Super Admin by dates' => ['admin-01@histologe.fr', 'bo-filters-dates', ['on' => '2023-05-01', 'off' => '2023-05-16']];
        yield 'Resp territoire by dates' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-dates', ['on' => '2023-05-01', 'off' => '2023-05-16']];
        yield 'Partenaire by dates' => ['user-13-01@histologe.fr', 'bo-filters-dates', ['on' => '2023-05-01', 'off' => '2023-05-16']];

        yield 'Super Admin by declarants' => ['admin-01@histologe.fr', 'bo-filters-declarants', '15'];
        yield 'Resp territoire by declarants' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-declarants', '15'];
        yield 'Partenaire by declarants' => ['user-13-01@histologe.fr', 'bo-filters-declarants', '15'];

        yield 'Super Admin by enfantsM6' => ['admin-01@histologe.fr', 'bo-filters-enfantsM6', ['1']];
        yield 'Resp territoire by enfantsM6' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-enfantsM6', ['1']];
        yield 'Partenaire by enfantsM6' => ['user-13-01@histologe.fr', 'bo-filters-enfantsM6', ['1']];

        yield 'Super Admin by interventions' => ['admin-01@histologe.fr', 'bo-filters-interventions', ['0']];
        yield 'Resp territoire by interventions' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-interventions', ['0']];
        yield 'Partenaire by interventions' => ['user-13-01@histologe.fr', 'bo-filters-interventions', ['0']];

        yield 'Super Admin by nde' => ['admin-01@histologe.fr', 'bo-filters-nde', ['NDE_AVEREE']];

        yield 'Super Admin by proprios' => ['admin-01@histologe.fr', 'bo-filters-proprios', ['0']];
        yield 'Resp territoire by proprios' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-proprios', ['0']];
        yield 'Partenaire by proprios' => ['user-13-01@histologe.fr', 'bo-filters-proprios', ['0']];

        yield 'Super Admin by visites' => ['admin-01@histologe.fr', 'bo-filters-visites', ['0']];
        yield 'Resp territoire by visites' => ['admin-territoire-13-01@histologe.fr', 'bo-filters-visites', ['0']];
        yield 'Partenaire by visites' => ['user-13-01@histologe.fr', 'bo-filters-visites', ['0']];
    }
}
