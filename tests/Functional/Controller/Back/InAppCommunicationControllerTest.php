<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class InAppCommunicationControllerTest extends WebTestCase
{
    #[DataProvider('provideUsersAndMessages')]
    public function testUserOnlySeesAndCanCloseCommunicationForRole(
        string $email,
        string $expectedMessage,
        string $unexpectedMessage,
        string $expectedType,
    ): void {
        self::ensureKernelShutdown();
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $dashboardUrl = $router->generate('back_dashboard');
        $crawler = $this->requestDashboard($client, $dashboardUrl);

        $notice = $crawler->filter('.fr-notice');
        $this->assertCount(1, $notice);
        $this->assertStringContainsString($expectedMessage, $notice->text());
        $this->assertStringNotContainsString($unexpectedMessage, $notice->text());
        $this->assertTrue($notice->matches(sprintf('.fr-notice--%s', $expectedType)));

        $closeUrl = $notice->filter('[data-close-url]')->attr('data-close-url');
        $client->request('POST', $closeUrl);

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"success":true}', (string) $client->getResponse()->getContent());

        $crawler = $this->requestDashboard($client, $dashboardUrl);

        $this->assertCount(0, $crawler->filter('.fr-notice'));
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function provideUsersAndMessages(): iterable
    {
        yield 'responsable de territoire' => [
            'admin-territoire-13-01@signal-logement.fr',
            'Que tous les RT dans la vibe lèvent le doigt',
            "On a pas d'amiral pour nous saper le moral",
            'warning',
        ];
        yield 'agent partenaire' => [
            'user-69-05@signal-logement.fr',
            "On a pas d'amiral pour nous saper le moral",
            'Que tous les RT dans la vibe lèvent le doigt',
            'info',
        ];
    }

    private function requestDashboard(KernelBrowser $client, string $dashboardUrl): Crawler
    {
        $crawler = $client->request('GET', $dashboardUrl);

        if ($client->getResponse()->isRedirect()) {
            $crawler = $client->followRedirect();
        }

        $this->assertResponseIsSuccessful();

        return $crawler;
    }
}
