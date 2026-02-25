<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementInjonctionControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testIndexAccessGranted(): void
    {
        $this->client->request('GET', $this->router->generate('back_injonction_signalement_index'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testIndexAccessDenied(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $this->client->request('GET', $this->router->generate('back_injonction_signalement_index'));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCourrierBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000011']);
        $this->assertNotNull($signalement);

        $this->client->request('GET', $this->router->generate(
            'back_injonction_signalement_courrier_bailleur',
            ['uuid' => $signalement->getUuid()]
        ));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
    }

    public function testCourrierBailleurFermeture(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000013']);
        $this->assertNotNull($signalement);

        $this->client->request('GET', $this->router->generate(
            'back_injonction_signalement_courrier_bailleur_injonction_closed',
            ['uuid' => $signalement->getUuid()]
        ));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
    }
}
