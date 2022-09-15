<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BackSignalementControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideRoutes
     */
    public function testSignalementSuccessfullyDisplay(string $route, Signalement $signalement)
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);

        $client->loginUser($user);
        $client->request('GET', $route);
        if (Signalement::STATUS_ARCHIVED !== $signalement->getStatut()) {
            $this->assertResponseIsSuccessful($signalement->getId());
            $this->assertSelectorTextContains('h1.fr-h2.fr-mb-2v',
                'Signalement #'.$signalement->getReference(),
                $signalement->getReference()
            );
        } else {
            $this->assertResponseRedirects('/bo/');
        }
    }

    public function provideRoutes(): \Generator
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        $signalements = $signalementRepository->findAll();

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $route = $generatorUrl->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);
            yield $route => [$route, $signalement];
        }
    }
}
