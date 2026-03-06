<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Signalement;
use App\Entity\TiersInvitation;
use App\Tests\UserHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementInvitationControllerTest extends WebTestCase
{
    use UserHelper;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testAccepterInvitation(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        /** @var Signalement $signalement */
        $signalement = $entityManager
            ->getRepository(Signalement::class)
            ->findOneBy(['reference' => '2024-01']);

        // On crée une invitation en base
        $invitation = new TiersInvitation();
        $invitation->setSignalement($signalement);
        $invitation->setLastname('Pote');
        $invitation->setFirstname('Paul');
        $invitation->setEmail('paulpote@gmail.com');

        $entityManager->persist($invitation);
        $entityManager->flush();

        $client->loginUser(
            $this->getSignalementUser($signalement),
            'code_suivi'
        );

        $url = $container->get(RouterInterface::class)->generate(
            'front_suivi_invitation_accepter',
            ['code' => $signalement->getCodeSuivi(), 'token' => $invitation->getToken()]
        );

        $client->request('GET', $url);

        $this->assertResponseRedirects(
            '/suivre-mon-signalement/'.$signalement->getCodeSuivi()
        );

        $entityManager->clear();

        $signalementReloaded = $entityManager
            ->getRepository(Signalement::class)
            ->find($signalement->getId());

        $this->assertEquals('paulpote@gmail.com', $signalementReloaded->getMailDeclarant());
        $this->assertTrue($invitation->isAccepted());
        $this->assertFalse($invitation->isRefused());
    }

    public function testRefuserInvitation(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $signalement = $entityManager
            ->getRepository(Signalement::class)
            ->findOneBy(['reference' => '2024-01']);

        $invitation = new TiersInvitation();
        $invitation->setSignalement($signalement);
        $invitation->setEmail('paulpote@gmail.com');
        $invitation->setLastname('Pote');
        $invitation->setFirstname('Paul');

        $entityManager->persist($invitation);
        $entityManager->flush();

        $client->loginUser(
            $this->getSignalementUser($signalement),
            'code_suivi'
        );

        $url = $container->get(RouterInterface::class)->generate(
            'front_suivi_invitation_refuser',
            ['code' => $signalement->getCodeSuivi(), 'token' => $invitation->getToken()]
        );

        $client->request('GET', $url);

        $this->assertResponseRedirects('/');

        $entityManager->clear();

        $signalementReloaded = $entityManager
            ->getRepository(Signalement::class)
            ->find($signalement->getId());

        $this->assertNull($signalementReloaded->getMailDeclarant());
        $this->assertTrue($invitation->isRefused());
        $this->assertFalse($invitation->isAccepted());
    }

    public function testAccepterInvitationAlreadyRefused(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        /** @var Signalement $signalement */
        $signalement = $entityManager
            ->getRepository(Signalement::class)
            ->findOneBy(['reference' => '2024-01']);

        // On crée une invitation en base
        $invitation = new TiersInvitation();
        $invitation->setSignalement($signalement);
        $invitation->setLastname('Pote');
        $invitation->setFirstname('Paul');
        $invitation->setEmail('paulpote@gmail.com');
        $invitation->refuse();

        $entityManager->persist($invitation);
        $entityManager->flush();

        $client->loginUser(
            $this->getSignalementUser($signalement),
            'code_suivi'
        );

        $url = $container->get(RouterInterface::class)->generate(
            'front_suivi_invitation_accepter',
            ['code' => $signalement->getCodeSuivi(), 'token' => $invitation->getToken()]
        );

        $client->request('GET', $url);

        $this->assertResponseRedirects('/');
    }

    public function testRefuserInvitationAlreadyAccepted(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $signalement = $entityManager
            ->getRepository(Signalement::class)
            ->findOneBy(['reference' => '2024-01']);

        $invitation = new TiersInvitation();
        $invitation->setSignalement($signalement);
        $invitation->setEmail('paulpote@gmail.com');
        $invitation->setLastname('Pote');
        $invitation->setFirstname('Paul');
        $invitation->accept();

        $entityManager->persist($invitation);
        $entityManager->flush();

        $client->loginUser(
            $this->getSignalementUser($signalement),
            'code_suivi'
        );

        $url = $container->get(RouterInterface::class)->generate(
            'front_suivi_invitation_refuser',
            ['code' => $signalement->getCodeSuivi(), 'token' => $invitation->getToken()]
        );

        $client->request('GET', $url);

        $this->assertResponseRedirects(
            '/suivre-mon-signalement/'.$signalement->getCodeSuivi()
        );
    }

    public function testRefuserInvitationAlreadyRefused(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $signalement = $entityManager
            ->getRepository(Signalement::class)
            ->findOneBy(['reference' => '2024-01']);

        $invitation = new TiersInvitation();
        $invitation->setSignalement($signalement);
        $invitation->setEmail('paulpote@gmail.com');
        $invitation->setLastname('Pote');
        $invitation->setFirstname('Paul');
        $invitation->refuse();

        $entityManager->persist($invitation);
        $entityManager->flush();

        $client->loginUser(
            $this->getSignalementUser($signalement),
            'code_suivi'
        );

        $url = $container->get(RouterInterface::class)->generate(
            'front_suivi_invitation_refuser',
            ['code' => $signalement->getCodeSuivi(), 'token' => $invitation->getToken()]
        );

        $client->request('GET', $url);

        $this->assertResponseRedirects('/');
    }
}
