<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileGenerateZipControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private RouterInterface $router;
    private Signalement $signalement;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);

        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);
        $this->client->loginUser($user);
    }

    public function testGenerateZipAllPhotosSuccess(): void
    {
        $route = $this->router->generate('back_signalement_generate_zip', ['uuid' => $this->signalement->getUuid()]);
        $this->client->request('GET', $route);
        $expectedRedirectRoute = $this->router->generate(
            'back_signalement_view',
            ['uuid' => $this->signalement->getUuid()]
        ).'#documents';

        $this->assertResponseRedirects($expectedRedirectRoute);
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-notice--success');
        $this->assertSelectorTextContains('.fr-notice--success', 'Les photos ont bien été envoyées');
    }

    public function testGenerateZipSelectionSuccess(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'zip_selection_'.$this->signalement->getUuid());
        $route = $this->router->generate('back_signalement_generate_zip_selection', ['uuid' => $this->signalement->getUuid()]);

        $payload = [
            'fileIds' => [1, 2],
            '_token' => $csrfToken,
        ];

        $this->client->request('POST', $route, $payload);

        $this->assertResponseRedirects('/bo/signalements/'.$this->signalement->getUuid().'#documents');
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-notice--success');
    }
}
