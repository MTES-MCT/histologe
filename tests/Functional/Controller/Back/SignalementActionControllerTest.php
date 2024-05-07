<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementActionControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private SuiviRepository $suiviRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->suiviRepository = static::getContainer()->get(SuiviRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);
    }

    public function testDeleteSuivi(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000006']);

        $route = $this->router->generate('back_signalement_delete_suivi', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $description = 'Un petit message de rappel afin d\'y revenir plus tard';
        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);

        $this->client->request(
            'POST',
            $route,
            [
                'suivi' => $suivi->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'signalement_delete_suivi_'.$signalement->getId()),
            ]
        );

        $suivi = $this->suiviRepository->findOneBy(['description' => $description]);
        $this->assertNotNull($suivi->getDeletedAt());
        $this->assertNotNull($suivi->getDeletedBy());
        $this->assertNotEquals($description, $suivi->getDescription());
        $this->assertStringContainsString(Suivi::DESCRIPTION_DELETED, $suivi->getDescription());
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid().'#suivis');
    }

    public function testSwitchValue(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000010']);

        $route = $this->router->generate('back_signalement_switch_value', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $this->client->request(
            'POST',
            $route,
            [
                'value' => 1,
                '_token' => $this->generateCsrfToken($this->client, 'KO'),
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertEquals('{"response":"error"}', $this->client->getResponse()->getContent());

        $this->client->request(
            'POST',
            $route,
            [
                'value' => 1,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_switch_value_'.$signalement->getUuid()),
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertEquals('{"response":"success"}', $this->client->getResponse()->getContent());

        $this->client->request(
            'POST',
            $route,
            [
                'value' => 3,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_switch_value_'.$signalement->getUuid()),
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertEquals('{"response":"success"}', $this->client->getResponse()->getContent());
        $this->assertEquals(1, $signalement->getTags()->count());
        $this->assertEquals(3, $signalement->getTags()->first()->getId());
    }
}
