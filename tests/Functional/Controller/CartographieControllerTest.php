<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartographieControllerTest extends WebTestCase
{
    private const SUPER_ADMIN = 'admin-01@histologe.fr';
    private const ADMIN_TERRITOIRE = 'admin-territoire-13-01@histologe.fr';
    private const PARTNER = 'user-13-01@histologe.fr';

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
        $client->request('POST', $route, [
            $filter => $terms,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function provideFilterSearch(): \Generator
    {
        yield 'Super Admin by statut Nouveau' => [self::SUPER_ADMIN, 'bo-filters-statuses', ['1']];
        yield 'Resp territoire by statut Nouveau' => [self::ADMIN_TERRITOIRE, 'bo-filters-statuses', ['1']];
        yield 'Partenaire by statut Nouveau' => [self::PARTNER, 'bo-filters-statuses', ['1']];
        yield 'Super Admin by Parc public/prive' => [self::SUPER_ADMIN, 'bo-filters-housetypes', ['1']];
        yield 'Resp territoire by Parc public/prive' => [self::ADMIN_TERRITOIRE, 'bo-filters-housetypes', ['1']];
        yield 'Partenaire by Parc public/prive' => [self::PARTNER, 'bo-filters-housetypes', ['1']];
        yield 'Super Admin by affectation closed' => [self::SUPER_ADMIN, 'bo-filters-closed_affectation', ['ONE_CLOSED']];
        yield 'Resp territoire by affectation closed' => [self::ADMIN_TERRITOIRE, 'bo-filters-closed_affectation', ['ONE_CLOSED']];
        yield 'Super Admin by city' => [self::SUPER_ADMIN, 'bo-filters-cities', ['Marseille']];
        yield 'Resp territoire by city' => [self::ADMIN_TERRITOIRE, 'bo-filters-cities', ['Marseille']];
        yield 'Partenaire by city' => [self::PARTNER, 'bo-filters-cities', ['Marseille']];
        yield 'Super Admin by partenaire' => [self::SUPER_ADMIN, 'bo-filters-partners', ['5']];
        yield 'Resp territoire by partenaire' => [self::ADMIN_TERRITOIRE, 'bo-filters-partners', ['5']];
        yield 'Super Admin by critère' => [self::SUPER_ADMIN, 'bo-filters-criteres', ['17']];
        yield 'Resp territoire by critère' => [self::ADMIN_TERRITOIRE, 'bo-filters-criteres', ['17']];
        yield 'Partenaire by critère' => [self::PARTNER, 'bo-filters-criteres', ['17']];
        yield 'Super Admin by territory' => [self::SUPER_ADMIN, 'bo-filters-territories', ['1']];
        yield 'Super Admin by Search Terms with Reference' => [self::SUPER_ADMIN, 'bo-filters-searchterms', '2022-1'];
        yield 'Resp territoire by Search Terms with cp Occupant' => [self::ADMIN_TERRITOIRE, 'bo-filters-searchterms', '13003'];
        yield 'Partenaire by Search Terms with cp Occupant 13005' => [self::PARTNER, 'bo-filters-searchterms', '13005'];
        yield 'Partenaire by Search Terms with city Occupant' => [self::PARTNER, 'bo-filters-searchterms', 'Gex'];
        yield 'Super Admin by Tags' => [self::SUPER_ADMIN, 'bo-filters-tags', ['3']];
        yield 'Resp territoire by Tags' => [self::ADMIN_TERRITOIRE, 'bo-filters-tags', ['3']];
        yield 'Partenaire by Tags' => [self::PARTNER, 'bo-filters-tags', ['3']];
        yield 'Super Admin by scores' => [self::SUPER_ADMIN, 'bo-filters-scores', ['on' => '3', 'off' => '20']];
        yield 'Resp territoire by scores' => [self::ADMIN_TERRITOIRE, 'bo-filters-scores', ['on' => '3', 'off' => '20']];
        yield 'Partenaire by scores' => [self::PARTNER, 'bo-filters-scores', ['on' => '3', 'off' => '20']];
        yield 'Super Admin by allocs' => [self::SUPER_ADMIN, 'bo-filters-allocs', ['CAF']];
        yield 'Resp territoire by allocs' => [self::ADMIN_TERRITOIRE, 'bo-filters-allocs', ['CAF']];
        yield 'Partenaire by allocs' => [self::PARTNER, 'bo-filters-allocs', ['CAF']];
        yield 'Super Admin by avant1949' => [self::SUPER_ADMIN, 'bo-filters-avant1949', ['1']];
        yield 'Resp territoire by avant1949' => [self::ADMIN_TERRITOIRE, 'bo-filters-avant1949', ['1']];
        yield 'Partenaire by avant1949' => [self::PARTNER, 'bo-filters-avant1949', ['1']];
        yield 'Super Admin by dates' => [self::SUPER_ADMIN, 'bo-filters-dates', ['on' => '2023-05-01', 'off' => '2023-05-16']];
        yield 'Resp territoire by dates' => [self::ADMIN_TERRITOIRE, 'bo-filters-dates', ['on' => '2023-05-01', 'off' => '2023-05-16']];
        yield 'Partenaire by dates' => [self::PARTNER, 'bo-filters-dates', ['on' => '2023-05-01', 'off' => '2023-05-16']];
        yield 'Super Admin by declarants' => [self::SUPER_ADMIN, 'bo-filters-declarants', '15'];
        yield 'Resp territoire by declarants' => [self::ADMIN_TERRITOIRE, 'bo-filters-declarants', '15'];
        yield 'Partenaire by declarants' => [self::PARTNER, 'bo-filters-declarants', '15'];
        yield 'Super Admin by enfantsM6' => [self::SUPER_ADMIN, 'bo-filters-enfantsM6', ['1']];
        yield 'Resp territoire by enfantsM6' => [self::ADMIN_TERRITOIRE, 'bo-filters-enfantsM6', ['1']];
        yield 'Partenaire by enfantsM6' => [self::PARTNER, 'bo-filters-enfantsM6', ['1']];
        yield 'Super Admin by interventions' => [self::SUPER_ADMIN, 'bo-filters-interventions', ['0']];
        yield 'Resp territoire by interventions' => [self::ADMIN_TERRITOIRE, 'bo-filters-interventions', ['0']];
        yield 'Partenaire by interventions' => [self::PARTNER, 'bo-filters-interventions', ['0']];
        yield 'Super Admin by nde' => [self::SUPER_ADMIN, 'bo-filters-nde', ['NDE_AVEREE']];
        yield 'Super Admin by proprios' => [self::SUPER_ADMIN, 'bo-filters-proprios', ['0']];
        yield 'Resp territoire by proprios' => [self::ADMIN_TERRITOIRE, 'bo-filters-proprios', ['0']];
        yield 'Partenaire by proprios' => [self::PARTNER, 'bo-filters-proprios', ['0']];
        yield 'Super Admin by visites' => [self::SUPER_ADMIN, 'bo-filters-visites', ['0']];
        yield 'Resp territoire by visites' => [self::ADMIN_TERRITOIRE, 'bo-filters-visites', ['0']];
        yield 'Partenaire by visites' => [self::PARTNER, 'bo-filters-visites', ['0']];
    }
}
