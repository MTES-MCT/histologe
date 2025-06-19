<?php

namespace App\Service\Gouv\Rial\Request;

class RialSearchLocauxParams
{
    /**
     * @return array<string, int|string|null>|null
     */
    public static function getFromBanId(string $banId): ?array
    {
        $banExploded = explode('_', $banId);
        if (\count($banExploded) < 3) {
            return null;
        }

        $codeCity = $banExploded[0];
        if ('97' === substr($codeCity, 0, 2)) {
            $codeDepartementInsee = substr($codeCity, 0, 3);
            $codeCommuneInsee = substr($codeCity, 3, 2);
        } else {
            $codeDepartementInsee = substr($codeCity, 0, 2);
            $codeCommuneInsee = substr($codeCity, 2, 3);
        }

        // Corse codes
        $codeDepartementInsee = strtoupper($codeDepartementInsee);

        $codeVoieTopo = $banExploded[1];
        $numeroVoirie = $banExploded[2];
        $numeroVoirie = (int) $numeroVoirie; // cleans '0' from the left of the string

        $indiceRepetitionNumeroVoirie = null;
        if (isset($banExploded[3]) && !empty($banExploded[3])) {
            $indiceRepetitionNumeroVoirie = substr(strtoupper($banExploded[3]), 0, 1);
        }

        return [
            'codeDepartementInsee' => $codeDepartementInsee,
            'codeCommuneInsee' => $codeCommuneInsee,
            'codeVoieTopo' => $codeVoieTopo,
            'numeroVoirie' => $numeroVoirie,
            'indiceRepetitionNumeroVoirie' => $indiceRepetitionNumeroVoirie,
        ];
    }
}
