<?php

namespace App\Utils\Address;

class EtageParser
{
    public static function parse(string $etage): ?int
    {
        if (preg_match('/(rez|rdc|rdj|rh|chauss)/i', $etage, $matches)) {
            return 0;
        } elseif (preg_match('/(sous-sol|sol)/i', $etage, $matches)) {
            return -1;
        } elseif (preg_match('/\d+/', $etage, $matches) && strlen($matches[0]) <= 3) {
            // la limite à 3 chiffres permet d'éviter d'envoyer des étages de plus de 3 caractères aux services esabora qui ne les acceptent pas
            // un numéro d'étage supérieur à 999 n'a de toute façon aucun sens dans un futur proche !
            return (int) $matches[0];
        }

        return null;
    }
}
