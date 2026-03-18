<?php

namespace App\Tests\Fake;

use App\Service\Gouv\Rnb\Response\RnbBuilding;
use App\Service\Gouv\Rnb\RnbService;

class RnbServiceFake extends RnbService
{
    public function __construct()
    {
        // Intentionally empty.
        // In test environment, this fake replaces the real service to avoid HTTP calls.
    }

    /**
     * @return array<RnbBuilding>
     */
    public function getBuildings(string $cleInteropBan): array
    {
        switch (trim($cleInteropBan)) {
            case '34172_4562_00151':
                $response = [
                    new RnbBuilding([
                        'rnb_id' => 'XM37BZPW9TPW',
                        'point' => [
                            'coordinates' => [3.887057368556204, 43.60053728208864],
                        ],
                    ]),
                    new RnbBuilding([
                        'rnb_id' => 'AJKTX1Y7THZE',
                        'point' => [
                            'coordinates' => [3.887854585446229, 43.60121148421197],
                        ],
                    ]),
                    new RnbBuilding([
                        'rnb_id' => '9MAZ2GAX8DYT',
                        'point' => [
                            'coordinates' => [3.887156836861802, 43.60096522940157],
                        ],
                    ]),
                    new RnbBuilding([
                        'rnb_id' => 'NQKZ7YWG8ZEX',
                        'point' => [
                            'coordinates' => [3.88783560657471, 43.60141893351633],
                        ],
                    ]),
                ];
                break;
            case '44179_0545_00008':
                $response = [
                    new RnbBuilding([
                        'rnb_id' => 'RK47CCE5R68T',
                        'point' => [
                            'coordinates' => [-1.410468591357994, 47.36078500836602],
                        ],
                    ]),
                    new RnbBuilding([
                        'rnb_id' => 'AN55H8K2BDXD',
                        'point' => [
                            'coordinates' => [-1.410553517209974, 47.36068409968665],
                        ],
                    ]),
                ];
                break;
            case '44069_0160_00005':
                $response = [
                    new RnbBuilding([
                        'rnb_id' => 'AZ57KYVASGD5',
                        'point' => [
                            'coordinates' => [-2.429891290608717, 47.29410462047548],
                        ],
                    ]),
                ];
                break;
            case '30348_0430_00015':
                $response = [
                    new RnbBuilding([
                        'rnb_id' => 'FQYN6F6WPEJ8',
                        'point' => [
                            'coordinates' => [4.141756415466935, 44.05309187516625],
                        ],
                    ]),
                ];
                break;
            default:
                $response = [];
        }

        return $response;
    }

    public function getBuilding(string $rnbId): ?RnbBuilding
    {
        return null;
    }
}
