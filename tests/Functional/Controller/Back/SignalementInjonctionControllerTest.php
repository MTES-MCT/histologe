<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifClotureUsager;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
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
        self::ensureKernelShutdown();
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

    public function testAdminCancelInjonctionProcedureSuccessfully(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $this->assertEquals(SignalementStatus::INJONCTION_BAILLEUR, $signalement->getStatut());

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Fermer le signalement',
            [
                'admin_cancel_injonction_procedure[reason]' => 'ACCORD_PROPRIETAIRE',
                'admin_cancel_injonction_procedure[details]' => 'Le propriétaire et l\'occupant se sont mis d\'accord à l\'amiable.',
            ]
        );

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertStringContainsString('/bo/signalement-injonction', $response['url']);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $this->assertEquals(SignalementStatus::INJONCTION_CLOSED, $signalement->getStatut());
        $this->assertEquals(MotifClotureUsager::ACCORD_PROPRIETAIRE, $signalement->getMotifClotureUsager());
        $this->assertEquals(MotifCloture::AUTRE, $signalement->getMotifCloture());
        $this->assertInstanceOf(\DateTimeInterface::class, $signalement->getClosedAt());
        $this->assertStringContainsString('se sont mis', (string) $signalement->getComCloture());
        $this->assertEquals($user->getId(), $signalement->getClosedBy()?->getId());

        // L'affectation en cours a également été clôturée avec le même motif
        $affectation = $signalement->getAffectations()->first();
        $this->assertNotFalse($affectation);
        $this->assertEquals(AffectationStatus::CLOSED, $affectation->getStatut());
        $this->assertEquals(MotifCloture::AUTRE, $affectation->getMotifCloture());

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = static::getContainer()->get(SuiviRepository::class);
        $suivi = $suiviRepository->findOneBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::INJONCTION_BAILLEUR_CLOTURE_PAR_ADMIN,
        ]);
        $this->assertNotNull($suivi);
        $this->assertTrue($suivi->getIsVisibleForUsager());
        $this->assertStringContainsString('Accord entre le propriétaire et l', (string) $suivi->getDescription());
    }

    public function testAdminCancelInjonctionProcedureFailsValidationWhenDetailsTooShort(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Fermer le signalement',
            [
                'admin_cancel_injonction_procedure[reason]' => 'ACCORD_PROPRIETAIRE',
                'admin_cancel_injonction_procedure[details]' => 'trop',
            ]
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Le message doit contenir au moins 10 caract\u00e8res.', (string) $client->getResponse()->getContent());

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $this->assertEquals(SignalementStatus::INJONCTION_BAILLEUR, $signalement->getStatut());
    }
}
