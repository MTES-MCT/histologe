<?php

namespace App\Utils;

class EscalierParser
{
    public static function parse(string $escalier): ?string
    {
        $escalier = strtoupper(trim($escalier));

        if (\strlen($escalier) <= 3) {
            return $escalier;
        }

        $keywords = [
            'ESCALIER' => ['ESC', 'ESC.'],
            'BATIMENT' => ['BAT', 'BAT.', 'BÂT', 'BÂT.', 'BâT', 'BâT.', 'BT', 'BâTIMENT', 'BÂTIMENT'],
            'ENTREE' => ['ENTRÉE', 'ENTRéE', 'ENTREE'],
            'PORTE' => [],
            'BLOC' => [],
            'ALLEE' => ['ALLÉE', 'ALLéE'],
            'ETAGE' => ['ÉTAGE', 'éTAGE'],
            'NUMERO' => ['N°', 'NUM', 'NUMéRO'],
        ];

        $match = null;

        foreach ($keywords as $keyword => $abbreviations) {
            $abbreviations[] = $keyword;
            $abbreviations = array_unique($abbreviations);
            $abbreviationsPattern = implode('|', $abbreviations);

            $pattern = '/\b('.$abbreviationsPattern.')\s+(.+?)\b/';
            if (preg_match($pattern, $escalier, $matches)) {
                if (!$match) {
                    $match = ['keyword' => $keyword, 'value' => substr(trim($matches[2]), 0, 3)];
                }
            }
        }

        if (preg_match('/N°\s*([0-9A-Z]+)/', $escalier, $matches)) {
            $value = strtoupper(substr(trim($matches[1]), 0, 3));
            if (!$match) {
                $match = ['keyword' => 'N°', 'value' => $value];
            }
        }

        $inputWithoutOrdinals = preg_replace('/\b(ER|ÈRE|EME|èRE|èME|IèME)\b/i', '', $escalier);
        if (preg_match('/\d+/', $inputWithoutOrdinals, $matches) && \strlen($inputWithoutOrdinals) <= 3) {
            $value = $matches[0];
            if (!$match) {
                $match = ['keyword' => 'NUM', 'value' => $value];
            }
        }

        if ($match) {
            return mb_trim($match['value']);
        }

        $pattern = '/\b(\d+)\s*(?:ER|ÈRE|EME|èRE|èME|IèME|IèM)?\s*(?=ESCALIER)/i';
        if (preg_match($pattern, $escalier, $matches)) {
            return substr($matches[1], 0, 3);
        }

        return null;
    }
}
