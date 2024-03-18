<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementEditControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testEditCoordonneesBailleur(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var RouterInterface $router */
        $router = $client->getContainer()->get(RouterInterface::class);

        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $signalement = $signalementRepository->findOneBy(['isLogementSocial' => true, 'villeOccupant' => 'Marseille']);
        $client->loginUser($user);
        $route = $router->generate('back_signalement_edit_coordonnees_bailleur', ['uuid' => $signalement->getUuid()]);

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
            $client,
            'signalement_edit_coordonnees_bailleur_'.$signalement->getId()
        );

        $client->request('POST', $route, [], [], [], json_encode($payload));

        $this->assertResponseIsSuccessful();

        $this->assertEquals('13 HABITAT', $signalement->getBailleur()->getName());
        $this->assertEquals('13 HABITAT', $signalement->getNomProprio());
    }
}
