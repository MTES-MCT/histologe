<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileControllerTest extends WebTestCase
{
    use SessionHelper;
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);
    }

    public function testAddFileSignalementNotDeny(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@histologe.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => '00000000-0000-0000-2023-000000000009']);
        $this->client->request('POST', $route);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/bo/signalements/00000000-0000-0000-2023-000000000009');
    }

    public function testAddFileSignalementDeny(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@histologe.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => '00000000-0000-0000-2023-000000000012']);
        $this->client->request('POST', $route);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditFileSignalementSucces(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000009']);
        $route = $this->router->generate('back_signalement_edit_file', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'POST',
            $route,
            [
                'file_id' => $signalement->getFiles()[0]->getId(),
                'documentType' => 'AUTRE',
                'description' => 'Comme on peux le voir la situation est critique, il faut agir rapidement.',
                '_token' => $this->generateCsrfToken($this->client, 'signalement_edit_file_'.$signalement->getId()),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.fr-alert--success p', 'La photo a bien été modifiée.');
    }

    public function testEditFileSignalementError(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000009']);
        $route = $this->router->generate('back_signalement_edit_file', ['uuid' => $signalement->getUuid()]);

        $message = 'Je vais écrire un roman, lorem ipsum dolor sit amet, consectetur adipiscing elit.
        Nulla nec purus feugiat, ultricies nunc nec, tincidunt nunc. Nulla facilisi.
        Nullam nec... Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus feugiat, ultricies nunc nec, tincidunt nunc.
        Nulla facilisi. Nullam nec...';

        $this->client->request(
            'POST',
            $route,
            [
                'file_id' => $signalement->getFiles()[0]->getId(),
                'documentType' => 'AUTRE',
                'description' => $message,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_edit_file_'.$signalement->getId()),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.fr-alert--error p', 'La description ne doit pas dépasser 255 caractères');
    }
}
