<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class ArchivedSignalementControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->signalementRepository = self::getContainer()->get(SignalementRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);
    }

    public function testArchivedSignalementIndex(): void
    {
        $route = $this->router->generate('back_archived_signalements_index');
        $this->client->request('GET', $route);
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h2', '2 signalements archivés');
    }

    public function testReactiveSignalement()
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy([
            'statut' => Signalement::STATUS_ARCHIVED,
        ]);

        $route = $this->router->generate('back_archived_signalements_reactiver', [
            'uuid' => $signalement->getUuid(),
        ]);

        $this->client->request(
            'POST',
            $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'signalement_reactive_'.$signalement->getId()),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }
}
