<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Signalement;
use App\Manager\UserManager;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class FrontSignalementControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testDisplaySuiviSignalement(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'codeSuivi' => '0123456789',
        ]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUser = $router->generate('front_suivi_signalement', [
            'code' => $signalement->getCodeSuivi(),
        ]).'?from[]='.$signalement->getMailOccupant();

        $crawler = $client->request('GET', $urlSuiviSignalementUser);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('Signalement #2023-18', $crawler->filter('h1')->text());
    }

    public function testPostUsagerResponse(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'codeSuivi' => '0123456789',
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

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$codeSuivi.'?from='.$emailOccupant);
    }

    private function getCommonValidPayload(): array
    {
        return [
            'isNotOccupant' => '0',
            'nomDeclarant' => 'John',
            'prenomDeclarant' => 'Doe',
            'telDeclarant' => '',
            'mailDeclarant' => 'admin-01@histologe.fr',
            'nomOccupant' => 'Doe',
            'prenomOccupant' => 'Jenifer',
            'telOccupant' => '0611571631',
            'telOccupantBis' => '',
            'mailOccupant' => 'john.doe@yopmail.com',
            'etageOccupant' => '',
            'escalierOccupant' => '',
            'numAppartOccupant' => '',
            'adresseAutreOccupant' => '',
            'nomProprio' => 'Arnold Doe',
            'adresseProprio' => '',
            'telProprio' => '',
            'mailProprio' => '',
            'situation' => [8 => ['critere' => [10 => ['criticite' => 11]]]],
            'details' => 'Trés problématique de vivre ici, pas mal de fssure dans le séjour et de l\'humidité dans la chambre des enfants',
            'isProprioAverti' => '0',
            'nbAdultes' => '2',
            'nbEnfantsM6' => '1',
            'nbEnfantsP6' => '2',
            'natureLogement' => 'maison',
            'superficie' => '1000',
            'isAllocataire' => '0',
            'numAllocataire' => '0',
            'isLogementSocial' => '0',
            'isPreavisDepart' => '0',
            'isRelogement' => '0',
            'isCguAccepted' => 'on',
        ];
    }

    private function getErrorPayload(): array
    {
        return [
            'isNotOccupant' => '0',
            'nomDeclarant' => 'John',
            'prenomDeclarant' => 'Doe',
            'telDeclarant' => '',
            'mailDeclarant' => 'admin-01@histologe.fr',
            'nomOccupant' => 'Doe',
            'prenomOccupant' => 'Jenifer',
            'telOccupant' => '0611571631',
            'telOccupantBis' => '',
            'mailOccupant' => 'john.doe@yopmail.com',
            'etageOccupant' => '',
            'escalierOccupant' => '',
            'numAppartOccupant' => '',
            'adresseAutreOccupant' => '',
            'nomProprio' => 'Arnold Doe',
            'adresseProprio' => '',
            'telProprio' => '+331123456987854521452145245',
            'mailProprio' => '',
            'situation' => [8 => ['critere' => [10 => ['criticite' => 11]]]],
            'details' => 'Trés problématique de vivre ici, pas mal de fssure dans le séjour et de l\'humidité dans la chambre des enfants',
            'isProprioAverti' => '0',
            'nbAdultes' => '2',
            'nbEnfantsM6' => '1',
            'nbEnfantsP6' => '2',
            'natureLogement' => 'maison',
            'superficie' => '1000',
            'isAllocataire' => '0',
            'numAllocataire' => '0',
            'isLogementSocial' => '0',
            'isPreavisDepart' => '0',
            'isRelogement' => '0',
            'isCguAccepted' => 'on',
            'adresseOccupant' => '45 Rue du Général de Gaulle',
            'villeOccupant' => 'Saint-Denis',
            'cpOccupant' => '',
            'geoloc' => ['lat' => 55.452091, 'lng' => -20.885586],
            'inseeOccupant' => '97411',
        ];
    }
}
