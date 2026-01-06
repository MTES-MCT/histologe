<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\ClubEventRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class ConfigClubEventControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testIndex(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_config_club_event_index');
        $client->request('GET', $route);

        $this->assertSelectorTextContains('h2', '6 événements trouvés');
    }

    public function testEdit(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var ClubEventRepository $clubEventRepository */
        $clubEventRepository = static::getContainer()->get(ClubEventRepository::class);
        $clubEvents = $clubEventRepository->findAll();
        $clubEvent = $clubEvents[0];

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_config_club_event_edit', ['id' => $clubEvent->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'club_event');
        $client->request('POST', $route, [
            'club_event' => [
                'name' => $clubEvent->getName(),
                'url' => $clubEvent->getUrl(),
                'dateEvent' => '2026-06-06 10:00',
                'userRoles' => ['ROLE_ADMIN_TERRITORY'],
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseRedirects();
        $this->assertEquals('2026-06-06 08:00', $clubEvent->getDateEvent()->format('Y-m-d H:i')); // 2026-06-06 10:00' in Paris timezone is 2 hours behind UTC
    }
}
