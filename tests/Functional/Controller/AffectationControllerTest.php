<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\MotifRefus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class AffectationControllerTest extends WebTestCase
{
    use SessionHelper;

    public const USER_ADMIN_TERRITORY_13 = 'admin-territoire-13-01@histologe.fr';
    public const SIGNALEMENT_REFERENCE = '2022-1';

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;
    private SignalementRepository $signalementRepository;
    private SuiviRepository $suiviRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $this->suiviRepository = self::getContainer()->get(SuiviRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $user = $this->userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITORY_13]);
        $this->client->loginUser($user);
    }

    public function testRejectAffectationSignalement(): void
    {
        $user = $this->userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITORY_13]);

        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => self::SIGNALEMENT_REFERENCE]);

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
                    'motifRefus' => MotifRefus::AUTRE->name,
                    'suivi' => 'Cela ne me concerne pas, voir avec un autre organisme',
                ],
                '_token' => $this->generateCsrfToken($this->client, $tokenId),
            ]
        );

        /** @var Suivi $suivi */
        $suivi = $this->suiviRepository->findOneBy(['signalement' => $signalement], ['createdAt' => 'DESC']);

        $this->assertTrue(
            str_contains(
                $suivi->getDescription(),
                'Cela ne me concerne pas, voir avec un autre organisme'
            )
        );
        $this->assertEquals(Suivi::TYPE_AUTO, $suivi->getType());
        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function testAcceptAffectationSignalement(): void
    {
        $user = $this->userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITORY_13]);

        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => self::SIGNALEMENT_REFERENCE]);

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
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy([
            'reference' => self::SIGNALEMENT_REFERENCE,
        ]);

        $routeSignalementView = $this->router->generate('back_signalement_view', [
            'uuid' => $signalement->getUuid(),
        ]);

        $crawler = $this->client->request('GET', $routeSignalementView);
        $token = $crawler->filter('#signalement-affectation-form input[name=_token]')->attr('value');

        $routeAffectationResponse = $this->router->generate('back_signalement_toggle_affectation', [
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
        /** @var NotificationEmail $message */
        foreach ($this->getMailerMessages() as $message) {
            foreach ($message->getTo() as $to) {
                $tos[] = $to->getAddress();
            }
        }
        $this->assertEquals(\count($tos), \count(array_unique($tos)));
    }
}
