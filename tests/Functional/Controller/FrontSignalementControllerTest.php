<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\UserManager;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class FrontSignalementControllerTest extends WebTestCase
{
    use SessionHelper;

    public function provideStatusSignalement(): \Generator
    {
        yield 'Actif' => [Signalement::STATUS_ACTIVE];
        yield 'Clôturé' => [Signalement::STATUS_CLOSED];
        yield 'Refusé' => [Signalement::STATUS_REFUSED];
        yield 'Archivé' => [Signalement::STATUS_ARCHIVED];
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testDisplaySuiviProcedure(int $status): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
        ]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUser = $router->generate('front_suivi_procedure', [
            'code' => $signalement->getCodeSuivi(),
        ]).'?from='.$signalement->getMailOccupant().'&suiviAuto='.Suivi::ARRET_PROCEDURE;

        $crawler = $client->request('GET', $urlSuiviSignalementUser);
        if (Signalement::STATUS_ARCHIVED === $status) {
            $this->assertResponseRedirects('/');
        } elseif (Signalement::STATUS_ACTIVE === $status) {
            $this->assertEquals('Signalement #2022-1', $crawler->filter('h1')->text());
        } else {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'?from='.$signalement->getMailOccupant());
        }
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testDisplaySuiviSignalement(int $status): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
        ]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUser = $router->generate('front_suivi_signalement', [
            'code' => $signalement->getCodeSuivi(),
        ]).'?from='.$signalement->getMailOccupant();

        $crawler = $client->request('GET', $urlSuiviSignalementUser);
        if (Signalement::STATUS_ARCHIVED === $status) {
            $this->assertResponseRedirects('/');
        } elseif (Signalement::STATUS_ACTIVE === $status) {
            $this->assertEquals('Signalement #2022-1', $crawler->filter('h1')->text());
        } elseif (Signalement::STATUS_CLOSED === $status) {
            $this->assertEquals('Votre signalement a été clôturé, vous ne pouvez plus envoyer de messages.', $crawler->filter('.fr-alert--error p')->text());
        } elseif (Signalement::STATUS_REFUSED === $status) {
            $this->assertEquals('Votre signalement a été refusé, vous ne pouvez plus envoyer de messages.', $crawler->filter('.fr-alert--error p')->text());
        }
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testPostUsagerResponse(int $status): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
        ]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUserResponse = $router->generate('front_suivi_signalement_user_response', [
            'code' => $codeSuivi = $signalement->getCodeSuivi(),
        ]);

        $client->request('POST', $urlSuiviSignalementUserResponse, [
            '_token' => $this->generateCsrfToken($client, 'signalement_front_response_'.$signalement->getUuid()),
            'signalement_front_response' => [
                'email' => $emailOccupant = $signalement->getMailOccupant(),
                'type' => UserManager::OCCUPANT,
                'content' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry',
            ],
            'signalement' => [
                'files' => [
                    'photos' => [
                        'blank.jpg' => 'blank-64969c273a28a.jpg',
                    ],
                    'documents' => [
                        'blank.pdf' => 'blank-64969be831063.pdf',
                    ],
                ],
            ],
        ]);
        if (Signalement::STATUS_ARCHIVED === $status) {
            $this->assertResponseRedirects('/');
        } elseif (Signalement::STATUS_ACTIVE === $status) {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$codeSuivi.'?from='.$emailOccupant);
        } else {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$codeSuivi);
        }
    }
}
