<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DiffExtension extends AbstractExtension
{
    private const string NULL_PLACEHOLDER = "\x00NULL\x00";

    public function getFilters(): array
    {
        return [
            new TwigFilter('diff', [$this, 'diff'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Compare deux textes et retourne le HTML avec les différences mises en évidence.
     *
     * @param string $text    Le texte à afficher
     * @param string $compare Le texte à comparer
     * @param string $mode    'old' pour montrer les suppressions, 'new' pour montrer les ajouts
     */
    public function diff(?string $text, ?string $compare, string $mode = 'new'): string
    {
        $text = html_entity_decode($text ?? self::NULL_PLACEHOLDER, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $compare = html_entity_decode($compare ?? self::NULL_PLACEHOLDER, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        if ($text === $compare) {
            return self::NULL_PLACEHOLDER === $text ? '<i>null</i>' : htmlspecialchars($text);
        }

        $oldWords = $this->tokenize('old' === $mode ? $text : $compare);
        $newWords = $this->tokenize('old' === $mode ? $compare : $text);

        $diff = $this->computeDiff($oldWords, $newWords);

        return $this->renderDiff($diff, $mode);
    }

    /**
     * Découpe le texte en tokens (mots et espaces).
     *
     * @return array<string>
     */
    private function tokenize(string $text): array
    {
        // Sépare en gardant les espaces et la ponctuation comme tokens séparés
        preg_match_all('/\S+|\s+/', $text, $matches);

        return $matches[0];
    }

    /**
     * Calcule le diff entre deux tableaux de mots en utilisant l'algorithme LCS (Longest Common Subsequence).
     *
     * @param array<string> $old
     * @param array<string> $new
     *
     * @return array<array{type: string, value: string}>
     */
    private function computeDiff(array $old, array $new): array
    {
        $oldLen = \count($old);
        $newLen = \count($new);

        // Matrice LCS
        $lcs = [];
        for ($i = 0; $i <= $oldLen; ++$i) {
            $lcs[$i] = array_fill(0, $newLen + 1, 0);
        }

        // Remplir la matrice LCS
        for ($i = 1; $i <= $oldLen; ++$i) {
            for ($j = 1; $j <= $newLen; ++$j) {
                if ($old[$i - 1] === $new[$j - 1]) {
                    $lcs[$i][$j] = $lcs[$i - 1][$j - 1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
                }
            }
        }

        // Reconstruire le diff à partir de la matrice LCS
        $diff = [];
        $i = $oldLen;
        $j = $newLen;

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $old[$i - 1] === $new[$j - 1]) {
                array_unshift($diff, ['type' => 'equal', 'value' => $old[$i - 1]]);
                --$i;
                --$j;
            } elseif ($j > 0 && (0 === $i || $lcs[$i][$j - 1] >= $lcs[$i - 1][$j])) {
                array_unshift($diff, ['type' => 'added', 'value' => $new[$j - 1]]);
                --$j;
            } elseif ($i > 0 && (0 === $j || $lcs[$i][$j - 1] < $lcs[$i - 1][$j])) {
                array_unshift($diff, ['type' => 'removed', 'value' => $old[$i - 1]]);
                --$i;
            }
        }
        $mergedDiff = [];
        foreach ($diff as $part) {
            if (!empty($mergedDiff) && $mergedDiff[count($mergedDiff) - 1]['type'] === $part['type']) {
                $mergedDiff[count($mergedDiff) - 1]['value'] .= $part['value'];
            } else {
                $mergedDiff[] = $part;
            }
        }

        return $mergedDiff;
    }

    /**
     * Génère le HTML à partir du diff.
     *
     * @param array<array{type: string, value: string}> $diff
     */
    private function renderDiff(array $diff, string $mode): string
    {
        $result = '';

        foreach ($diff as $part) {
            $value = self::NULL_PLACEHOLDER === $part['value'] ? '<small><i>null</i></small>' : htmlspecialchars($part['value']);

            switch ($part['type']) {
                case 'equal':
                    $result .= $value;
                    break;
                case 'added':
                    if ('new' === $mode) {
                        $result .= '<ins class="diff-added">'.$value.'</ins>';
                    }
                    break;
                case 'removed':
                    if ('old' === $mode) {
                        $result .= '<del class="diff-removed">'.$value.'</del>';
                    }
                    break;
            }
        }

        return $result;
    }
}
