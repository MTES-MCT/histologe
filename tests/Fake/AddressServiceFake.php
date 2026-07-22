<?php

namespace App\Tests\Fake;

use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Ban\Response\BanAddress;
use App\Service\Gouv\Ban\Response\Poi;

class AddressServiceFake extends AddressService
{
    public function __construct()
    {
        // Intentionally empty.
        // In test environment, this fake replaces the real service to avoid HTTP calls.
    }

    /**
     * @return array<string, mixed>|null
     */
    public function searchAddress(string $query): ?array
    {
        return null;
    }

    public function getAddress(string $address): BanAddress
    {
        switch (trim($address)) {
            case '8 Rue de la tourmentinerie 44850 Saint-Mars-du-Désert':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [
                        [
                            'type' => 'Feature',
                            'properties' => [
                                'label' => $address,
                                'score' => 0.9590863636363635,
                                'housenumber' => '8',
                                'id' => '44179_0545_00008',
                                'banId' => '2ac4d3cd-67ee-46d4-9b5f-207bc6143aab',
                                'name' => '8 Rue de la tourmentinerie',
                                'street' => 'Rue de la tourmentinerie',
                                'postcode' => '44850',
                                'citycode' => '44179',
                                'city' => 'Saint-Mars-du-Désert',
                            ],
                            'geometry' => [
                                'type' => 'Point',
                                'coordinates' => [-1.410753, 	47.360679],
                            ],
                        ],
                    ],
                    'query' => $address,
                ]);
                break;
            case 'Route des Funeries 44850 Le Cellier':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9593136363636363,
                            'id' => '44028_886o32',
                            'banId' => '8124f4cf-3501-46de-8e83-8470939c871f',
                            'name' => 'Route des Funeries',
                            'street' => 'Route des Funeries',
                            'postcode' => '44850',
                            'citycode' => '44028',
                            'city' => 'Le Cellier',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [-1.362129, 47.33808],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '5 Rue Basse 44350 Guérande':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9641827272727271,
                            'housenumber' => '5',
                            'id' => '44069_0160_00005',
                            'banId' => '9c7fb87f-15db-41ac-9444-4cb3745b7ebc',
                            'name' => '5 Rue Basse',
                            'street' => 'Rue Basse',
                            'postcode' => '44350',
                            'citycode' => '44069',
                            'city' => 'Guérande',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [-2.429887, 47.294007],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '5 Rue Basse 30360 Vézénobres':
            case '5 Rue basse 30360 Vézénobres':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.956319090909091,
                            'housenumber' => '5',
                            'id' => '30348_0120_00005',
                            'banId' => '86abaa7b-74e0-4e00-8e2e-a141929765b7',
                            'name' => '5 Rue Basse',
                            'street' => 'Rue Basse',
                            'postcode' => '30360',
                            'citycode' => '30348',
                            'city' => 'Vézénobres',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [4.141351, 44.052612],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '151 Avenue du Pont Trinquat 34070 Montpellier':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9825954545454546,
                            'housenumber' => '151',
                            'id' => '34172_4562_00151',
                            'banId' => '86abaa7b-74e0-4e00-8e2e-a141929765b7',
                            'name' => '151 Avenue du Pont Trinquat',
                            'street' => 'Avenue du Pont Trinquat',
                            'postcode' => '34070',
                            'citycode' => '34172',
                            'city' => 'Montpellier',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [3.886628, 43.600582],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '10 Rue de la Paix 75002 Paris':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.96,
                            'housenumber' => '10',
                            'id' => '75102_7060_00010',
                            'banId' => 'b1b1b1b1-b1b1-b1b1-b1b1-b1b1b1b1b1b1',
                            'name' => '10 Rue de la Paix',
                            'street' => 'Rue de la Paix',
                            'postcode' => '75002',
                            'citycode' => '75102',
                            'city' => 'Paris',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [2.330, 48.869],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case 'Chemin du grand méchant loup 30360 Vézénobres':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.4718951801029159,
                            'housenumber' => '151',
                            'id' => '30348_0398',
                            'banId' => 'ec666aff-183d-4039-b700-00fec891a456',
                            'name' => 'Chemin du Mas du Pont',
                            'street' => 'Chemin du Mas du Pont',
                            'postcode' => '30360',
                            'citycode' => '30348',
                            'city' => 'Vézénobres',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [4.12549, 44.038739],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '30360 Vézénobres':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9382727272727273,
                            'banId' => '1506d598-b5c0-47e9-b4b0-b4827821a067',
                            'postcode' => '30360',
                            'citycode' => '30348',
                            'city' => 'Vézénobres',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [4.136449, 44.049792],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '34300 Agde':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9508972727272726,
                            'banId' => '5e68a849-f47f-474d-92df-aee8d6ca5b31',
                            'postcode' => '34300',
                            'citycode' => '34003',
                            'city' => 'Agde',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [3.484797, 43.302779],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '2 impasse de la peupleraie 44850 Saint-Mars-du-Désert':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.954561818181818,
                            'housenumber' => '2',
                            'id' => '44179_0470_00002',
                            'banId' => 'c64486f8-9f6f-4d8b-9648-e186357d8c43',
                            'name' => '2 Impasse de la peupleraie',
                            'street' => 'Impasse de la peupleraie',
                            'postcode' => '44850',
                            'citycode' => '44179',
                            'city' => 'Saint-Mars-du-Désert',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [-1.417255, 	47.368537],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '34 Rue des Arawaks 97223 Le Diamant':
                $response = new Address([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9571472727272726,
                            'housenumber' => '34',
                            'id' => '97206_0021_00034',
                            'banId' => '31916f1c-1e82-4a21-86d8-836eb6c340e9',
                            'name' => '34 Rue des Arawaks',
                            'postcode' => '97223',
                            'citycode' => '97206',
                            'city' => 'Le Diamant',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [-61.030632, 14.47869],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '29 Rue Désirée Clary 13002 Marseille':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.96,
                            'housenumber' => '29',
                            'id' => '13202_2333_00029',
                            'banId' => '9c7fb87f-15db-41ac-9444-4cb3745b7ebd',
                            'name' => '29 Rue Désirée Clary',
                            'street' => 'Rue Désirée Clary',
                            'postcode' => '13002',
                            'citycode' => '13202',
                            'city' => 'Marseille',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [5.3705315, 43.3085624],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '13002 Marseille':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9382727272727273,
                            'id' => '13202',
                            'postcode' => '13002',
                            'citycode' => '13202',
                            'city' => 'Marseille 2e Arrondissement',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [5.367338, 43.315273],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '01170 Gex':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9382727272727273,
                            'id' => '01173',
                            'postcode' => '01170',
                            'citycode' => '01173',
                            'city' => 'Gex',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [6.06653, 46.337184],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '13104 Arles':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9382727272727273,
                            'id' => '13104',
                            'postcode' => '13104',
                            'citycode' => '13104',
                            'city' => 'Arles',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [4.645086, 43.596721],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '69001 Lyon':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9382727272727273,
                            'id' => '69123',
                            'postcode' => '69001',
                            'citycode' => '69123',
                            'city' => 'Lyon',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [4.835, 45.758],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '29100 Douarnenez':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9382727272727273,
                            'id' => '29046',
                            'postcode' => '29100',
                            'citycode' => '29046',
                            'city' => 'Douarnenez',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [-4.325157, 48.082744],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            case '29200 Brest':
                $response = new BanAddress([
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'properties' => [
                            'label' => $address,
                            'score' => 0.9382727272727273,
                            'id' => '29019',
                            'postcode' => '29200',
                            'citycode' => '29019',
                            'city' => 'Brest',
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [-4.497736, 48.406435],
                        ],
                    ]],
                    'query' => $address,
                ]);
                break;
            default:
                $response = new BanAddress();
        }

        return $response;
    }

    public function getMunicipalityByCityCode(string $cityName, string $cityCode): ?Poi
    {
        return null;
    }
}
