<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SubscriptionsChoiceControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?UserRepository $userRepository = null;
    private ?User $user = null;
    private ?SignalementRepository $signalementRepository = null;
    private ?UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository = null;
    private ?RouterInterface $router = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /* @var RouterInterface $router */
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->userSignalementSubscriptionRepository = static::getContainer()->get(UserSignalementSubscriptionRepository::class);
        $this->user = $this->userRepository->findOneBy(['email' => 'user-13-05@signal-logement.fr']);
        $this->client->loginUser($this->user);
    }

    public function testSubscriptionChoiceAll(): void
    {
        $payload = json_encode([
            'subscriptions_choice' => '0',
            '_token' => $this->generateCsrfToken($this->client, 'subscriptions_choice'),
        ]);
        $route = $this->router->generate('subscriptions_choice');
        $this->client->request('POST', $route, [], [], [], (string) $payload);

        $subs = $this->userSignalementSubscriptionRepository->findBy(['user' => $this->user]);
        $this->assertCount(4, $subs);
        $this->assertEquals($this->user->hasDoneSubscriptionsChoice(), true);
    }

    public function testSubscriptionChoiceNone(): void
    {
        $otherSignalement = $this->signalementRepository->findOneBy(['reference' => '2023-12']);
        $subscription = new UserSignalementSubscription();
        $subscription->setSignalement($otherSignalement);
        $subscription->setUser($this->user);
        $subscription->setCreatedBy($this->user);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $payload = json_encode([
            'subscriptions_choice' => '1',
            '_token' => $this->generateCsrfToken($this->client, 'subscriptions_choice'),
        ]);
        $route = $this->router->generate('subscriptions_choice');
        $this->client->request('POST', $route, [], [], [], (string) $payload);

        $subs = $this->userSignalementSubscriptionRepository->findBy(['user' => $this->user]);
        $this->assertCount(2, $subs);
        $this->assertEquals($this->user->hasDoneSubscriptionsChoice(), true);
    }
}
