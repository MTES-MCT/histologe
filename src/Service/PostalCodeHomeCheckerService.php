<?php

namespace App\Service;

use App\Repository\TerritoryRepository;

class PostalCodeHomeCheckerService
{
    private static $list = [
        '04' => 'https://guichetunique04.lhi.histologe.fr',
        '06' => 'https://habitat-indigne06.histologe.fr',
        '13' => 'https://habitatindigne13.histologe.fr',
        '20' => [
            'list' => [
                '20000', '20090', '20167',
            ],
            'url' => 'https://signalicasa.histologe.fr',
        ],
        '31' => 'https://haute-garonne.histologe.fr',
        '38' => 'https://isere.histologe.fr',
        '47' => [
            'list' => [
                '47002', '47024', '47028', '47046', '47056', '47059', '47061', '47065', '47068', '47074', '47088',
                '47094', '47095', '47101', '47108', '47110', '47112', '47120', '47127', '47130', '47131', '47150',
                '47156', '47157', '47159', '47163', '47165', '47191', '47200', '47216', '47231', '47232', '47233',
                '47257', '47263', '47277', '47285', '47298', '47301', '47304', '47310', '47316', '47325', '47326',
            ],
            'url' => 'https://signaltoit.histologe.fr',
        ],
        '59' => [
            'list' => [
                '59000', '59100', '59110', '59112', '59113', '59115', '59116', '59117', '59118', '59120', '59126',
                '59130', '59134', '59136', '59139', '59150', '59152', '59155', '59160', '59166', '59170', '59175',
                '59184', '59185', '59193', '59200', '59211', '59221', '59223', '59236', '59237', '59249', '59250',
                '59251', '59260', '59262', '59263', '59272', '59273', '59274', '59280', '59290', '59320', '59350',
                '59370', '59390', '59420', '59480', '59496', '59510', '59520', '59560', '59650', '59700', '59780',
                '59790', '59810', '59830', '59840', '59890', '59510', '59910', '59930', '59960',
            ],
            'url' => 'https://amelio.histologe.fr',
        ],
        '64' => [
            'list' => [
                '64000', '64037', '64041', '64059', '64060', '64072', '64080', '64121', '64129', '64132', '64139',
                '64142', '64198', '64230', '64237', '64269', '64284', '64315', '64329', '64335', '64348', '64373',
                '64376', '64439', '64445', '64448', '64518', '64525', '64549', '64550',
            ],
            'url' => 'https://agglo-pau.histologe.fr',
        ],
        '76' => [
            'list' => [
                '76014', '76017', '76064', '76079', '76117', '76167', '76169', '76196', '76206', '76238', '76239',
                '76250', '76254', '76268', '76270', '76275', '76296', '76303', '76305', '76307', '76314', '76341',
                '76351', '76357', '76361', '76404', '76409', '76447', '76477', '76481', '76489', '76501', '76508',
                '76522', '76533', '76534', '76551', '76552', '76563', '76586', '76595', '76596', '76609', '76615',
                '76616', '76647', '76657', '76658', '76660', '76693', '76714', '76716', '76734', '76741',
            ],
            'url' => 'https://havreseinemetropole.histologe.fr',
        ],
    ];

    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    /**
     * Vérifie dans la liste si un code postal peut déjà être redirigé vers un service existant.
     */
    public function getRedirection(string $postal_code)
    {
        // Découpe pour avoir les deux premiers caractères saisis
        $zip = substr($postal_code, 0, 2);
        if (!empty(self::$list[$zip])) {
            // Si il n'y a pas de sous-liste pour ce département, on retourne directement le résultat
            if (empty(self::$list[$zip]['list'])) {
                return self::$list[$zip].'/signalement';
            }

            // Si il y a une sous-liste, on vérifie que c'est dans celle-ci et on retourne l'url
            if (\in_array($postal_code, self::$list[$zip]['list'])) {
                return self::$list[$zip]['url'].'/signalement';
            }
        }

        // Vérifie si le territoire existe et est activé dans la base de données
        $territoryItems = $this->territoryRepository->findByZip($zip);
        // Si c'est le cas, on redirige sur le site lui-même
        if (!empty($territoryItems)) {
            return 'local';
        }

        // Sinon, ce n'est pas encore pris en compte
        return false;
    }
}
