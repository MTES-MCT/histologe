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

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$codeSuivi.'?from='.$emailOccupant);
    }

    public function provideAdressSignalementWithTerritoryActive(): array
    {
        return [
            'La Réunion' => [[
                'adresseOccupant' => '45 Rue du Général de Gaulle',
                'villeOccupant' => 'Saint-Denis',
                'cpOccupant' => '97400',
                'geoloc' => ['lat' => 55.452091, 'lng' => -20.885586],
                'inseeOccupant' => '97411',
            ]],
            'La Martinique' => [[
                'adresseOccupant' => '4 Avenue Louis Moreau Gottschalk',
                'villeOccupant' => 'Schoelcher',
                'cpOccupant' => '97233',
                'geoloc' => ['lat' => 14.616930, 'lng' => -61.086790],
                'inseeOccupant' => '97229',
            ]],
            'Corse du Sud' => [[
                'adresseOccupant' => '3 Boulevard du Roi Jerome',
                'villeOccupant' => 'Ajjacio',
                'cpOccupant' => '20000',
                'geoloc' => ['lat' => 41.920468, 'lng' => 8.738425],
                'inseeOccupant' => '2A004',
            ]],
            'Alpes Maritimes' => [[
                'adresseOccupant' => '28 Avenue Felix Faure',
                'villeOccupant' => 'Menton',
                'cpOccupant' => '06500',
                'geoloc' => ['lat' => 43.774674, 'lng' => 7.502405],
                'inseeOccupant' => '06083',
            ]],
            'Côte-d\'Or' => [[
                'adresseOccupant' => '15 Rue des Godrans',
                'villeOccupant' => 'Dijon',
                'cpOccupant' => '21000',
                'geoloc' => ['lat' => 47.324467, 'lng' => 5.039006],
                'inseeOccupant' => '21231',
            ]],
            'Bastia' => [[
                'adresseOccupant' => '2 rue de la marine',
                'villeOccupant' => 'Bastia',
                'cpOccupant' => '20200',
                'geoloc' => ['lat' => 42.70278, 'lng' => 9.45],
                'inseeOccupant' => '2B033',
            ]],
            'Chenelette' => [[
                'adresseOccupant' => '7 place Ganelon',
                'villeOccupant' => 'Chenelette',
                'cpOccupant' => '69430',
                'geoloc' => ['lat' => 46.166672, 'lng' => 4.48333],
                'inseeOccupant' => '69054',
            ]],
            'Lyon 1er arrondissement' => [[
                'adresseOccupant' => 'Ruelle des Fantasques',
                'villeOccupant' => 'Lyon',
                'cpOccupant' => '69001',
                'geoloc' => ['lat' => 45.7640, 'lng' => 4.8357],
                'inseeOccupant' => '69381',
            ]],
        ];
    }

    public function provideAdressSignalementWithTerritoryInactive(): array
    {
        return [
            'Paris' => [[
                'adresseOccupant' => '174 Quai de Jemmapes',
                'villeOccupant' => 'Paris',
                'cpOccupant' => '75010',
                'geoloc' => ['lat' => 55.452091, 'lng' => -20.885586],
                'inseeOccupant' => '75110',
            ]],
            'Seine et Marne' => [[
                'adresseOccupant' => '84 Avenue Charles Rouxel',
                'villeOccupant' => 'Pontault-Combault',
                'cpOccupant' => '77340',
                'geoloc' => ['lat' => 48.791284, 'lng' => 2.603893],
                'inseeOccupant' => '77373',
            ]],
        ];
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
