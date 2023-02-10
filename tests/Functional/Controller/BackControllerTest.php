<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BackControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    /**
     * @dataProvider provideUserEmail
     */
    public function testListSignalementSuccessfullyOrRedirectWithoutError500(string $email): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_index');
        $client->request('GET', $route);
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
            yield $user->getEmail() => [$user->getEmail()];
        }
    }

    public function testDisplayGitBookDocumentationExternalLink(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
        $crawler = $client->request('GET', $generatorUrl->generate('back_index'));

        $this->assertSelectorTextContains('.fr-sidemenu ul:nth-of-type(2)', 'Documentation');
        $link = $crawler->selectLink('Documentation')->link();
        $this->assertEquals('https://documentation.histologe.beta.gouv.fr', $link->getUri());
    }

    public function testDisplaySignalementMDLRoleAdminTerritory()
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $user = $userRepository->findOneBy(['email' => 'admin-territoire-69-mdl@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('#signalements-result', '2023-3');
        $this->assertSelectorTextContains('#signalements-result', '2023-4');
    }

    public function testDisplaySignalementCORRoleAdminTerritory()
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $user = $userRepository->findOneBy(['email' => 'admin-territoire-69-cor@histologe.fr']);
        $client->loginUser($user);
        $route = $generatorUrl->generate('back_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('#signalements-result', '2023-2');
        $this->assertSelectorTextContains('#signalements-result', '2023-5');
    }
}
