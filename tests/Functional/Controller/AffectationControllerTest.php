<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class AffectationControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function testRejectAffectationSignalement(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        /** @var UserRepository $userRepository */
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $this->client->loginUser($user);

        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);

        $routeAffectationResponse = $this->router->generate('back_signalement_affectation_response', [
            'signalement' => $signalement->getId(),
            'affectation' => $signalement->getAffectations()->first()->getId(),
            'user' => $user->getId(),
        ]);

        $tokenId = 'signalement_affectation_response_'.$signalement->getId();
        $this->client->request(
            'POST',
            $routeAffectationResponse,
            [
                'signalement-affectation-response' => [
                    'suivi' => 'Cela ne me concerne pas, voir avec un autre organisme',
                ],
                '_token' => $this->generateCsrfToken($this->client, $tokenId),
            ]
        );

        /** @var Suivi $suivi */
        $suivi = $entityManager->getRepository(Suivi::class)->findOneBy(
            ['signalement' => $signalement],
            ['createdAt' => 'DESC']
        );

        $this->assertEmailCount(1);
        $this->assertTrue(str_contains($suivi->getDescription(), 'Cela ne me concerne pas, voir avec un autre organisme'));
        $this->assertEquals(Suivi::TYPE_AUTO, $suivi->getType());
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function testAcceptAffectationSignalement(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $this->client->loginUser($user);

        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);

        $routeAffectationResponse = $this->router->generate('back_signalement_affectation_response', [
            'signalement' => $signalement->getId(),
            'affectation' => $signalement->getAffectations()->first()->getId(),
            'user' => $user->getId(),
        ]);

        $tokenId = 'signalement_affectation_response_'.$signalement->getId();
        $this->client->request(
            'POST',
            $routeAffectationResponse,
            [
                'signalement-affectation-response' => [
                    'accept' => 1,
                    'suivi' => '',
                ],
                '_token' => $this->generateCsrfToken($this->client, $tokenId),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function testCheckingNoDuplicatedMailSentWhenPartnerAffectationIsMultiple(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $this->client->loginUser($user);

        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-1']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $routeSignalementView = $router->generate('back_signalement_view', [
            'uuid' => $signalement->getUuid(),
        ]);

        $crawler = $this->client->request('GET', $routeSignalementView);
        $token = $crawler->filter('#signalement-affectation-form input[name=_token]')->attr('value');

        $routeAffectationResponse = $router->generate('back_signalement_toggle_affectation', [
            'uuid' => $signalement->getUuid(),
        ]);

        $this->client->request('POST', $routeAffectationResponse, [
            'signalement-affectation' => [
                'partners' => [3, 4, 5],
            ],
            '_token' => $token,
        ]);

        $this->assertEmailCount(3);
        $tos = [];
        foreach ($this->getMailerMessages() as $message) {
            foreach ($message->getTo() as $to) {
                $tos[] = $to->getAddress();
            }
        }
        $this->assertEquals(\count($tos), \count(array_unique($tos)));
    }
}
