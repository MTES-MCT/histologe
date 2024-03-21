<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementEditControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /* @var UserRepository $userRepository */
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        /* @var SignalementRepository $signalementRepository */
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /* @var RouterInterface $router */
        $this->router = static::getContainer()->get(RouterInterface::class);
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);
    }

    public function testEditCoordonneesBailleurWithBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy([
            'isLogementSocial' => true,
            'villeOccupant' => 'Marseille',
        ]);

        $route = $this->router->generate(
            'back_signalement_edit_coordonnees_bailleur',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = [
            'nom' => '13 HABITAT',
            'prenom' => '',
            'mail' => 'contact@13habitat.fr',
            'telephone' => '0611000000',
            'ville' => 'Marseille',
            'beneficiaireRsa' => '',
            'beneficiaireFsl' => '',
            'revenuFiscal' => '',
            'dateNaissance' => '',
        ];

        $payload['_token'] = $this->generateCsrfToken(
            $this->client,
            'signalement_edit_coordonnees_bailleur_'.$signalement->getId()
        );

        $this->client->request('POST', $route, [], [], [], json_encode($payload));

        $this->assertResponseIsSuccessful();

        $this->assertEquals('13 HABITAT', $signalement->getBailleur()->getName());
        $this->assertEquals('13 HABITAT', $signalement->getNomProprio());
    }

    public function testEditCoordonneesBailleurWithCustomBailleur(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
        $route = $this->router->generate(
            'back_signalement_edit_coordonnees_bailleur',
            ['uuid' => $signalement->getUuid()]
        );

        $payload = [
            'nom' => 'Habitat Social Solidaire',
            'prenom' => '',
            'mail' => 'contact@13habitat.fr',
            'telephone' => '0611000000',
            'ville' => 'Marseille',
            'beneficiaireRsa' => '',
            'beneficiaireFsl' => '',
            'revenuFiscal' => '',
            'dateNaissance' => '',
        ];

        $payload['_token'] = $this->generateCsrfToken(
            $this->client,
            'signalement_edit_coordonnees_bailleur_'.$signalement->getId()
        );

        $this->client->request('POST', $route, [], [], [], json_encode($payload));

        $this->assertResponseIsSuccessful();
        $this->assertNull($signalement->getBailleur());
        $this->assertEquals('Habitat Social Solidaire', $signalement->getNomProprio());
    }
}
