<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class FrontSignalementControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideAdressSignalementWithTerritoryActive
     */
    public function testEnvoiSignalementFromActiveTerritory(array $adressSignalement)
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlPostSignalement = $router->generate('envoi_signalement');

        $payload = $this->getCommonPayload();
        $payloadSignalement = ['signalement' => array_merge($payload, $adressSignalement)];

        $client->request('POST', $urlPostSignalement, $payloadSignalement);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $bodyContent = $client->getResponse()->getContent();
        $this->assertEquals(json_decode($bodyContent, true)['response'], 'success');
    }

    /**
     * @dataProvider provideAdressSignalementWithTerritoryInactive
     */
    public function testEnvoiSignalementFromInactiveTerritory(array $adressSignalement)
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlPostSignalement = $router->generate('envoi_signalement');

        $payload = $this->getCommonPayload();
        $payloadSignalement = ['signalement' => array_merge($payload, $adressSignalement)];

        $client->request('POST', $urlPostSignalement, $payloadSignalement);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $bodyContent = $client->getResponse()->getContent();
        $this->assertEquals(json_decode($bodyContent, true)['response'], 'Territory is inactive');
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

    private function getCommonPayload()
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
            'isSituationHandicap' => '0',
            'isLogementSocial' => '0',
            'isPreavisDepart' => '0',
            'isRelogement' => '0',
            'isCguAccepted' => 'on',
        ];
    }
}
