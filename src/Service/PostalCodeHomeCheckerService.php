<?php

namespace App\Service;

use App\Repository\TerritoryRepository;

class PostalCodeHomeCheckerService
{
    private static $list = [
        '06' => 'https://habitat-indigne06.histologe.fr',
        '13' => 'https://habitatindigne13.histologe.fr',
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
