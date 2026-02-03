<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DiffExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('diff', [$this, 'diff'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Compare deux textes et retourne le HTML avec les différences mises en évidence.
     *
     * @param string      $text     Le texte à afficher
     * @param string      $compare  Le texte à comparer
     * @param string      $mode     'old' pour montrer les suppressions, 'new' pour montrer les ajouts
     * @param string|null $cssClass Classe CSS personnalisée (optionnel)
     */
    public function diff(?string $text, ?string $compare, string $mode = 'new', ?string $cssClass = null): string
    {
        if (null === $text && null === $compare) {
            return '';
        }

        if (null === $text) {
            $text = '';
        }

        if (null === $compare) {
            $compare = '';
        }

        if ($text === $compare) {
            return htmlspecialchars($text);
        }

        $oldWords = $this->tokenize('old' === $mode ? $text : $compare);
        $newWords = $this->tokenize('old' === $mode ? $compare : $text);

        $diff = $this->computeDiff($oldWords, $newWords);

        return $this->renderDiff($diff, $mode, $cssClass);
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

        return $diff;
    }

    /**
     * Génère le HTML à partir du diff.
     *
     * @param array<array{type: string, value: string}> $diff
     */
    private function renderDiff(array $diff, string $mode, ?string $cssClass): string
    {
        $result = '';

        foreach ($diff as $part) {
            $value = htmlspecialchars($part['value']);

            switch ($part['type']) {
                case 'equal':
                    $result .= $value;
                    break;
                case 'added':
                    if ('new' === $mode) {
                        $class = $cssClass ?? 'diff-added';
                        $result .= '<ins class="'.$class.'">'.$value.'</ins>';
                    }
                    break;
                case 'removed':
                    if ('old' === $mode) {
                        $class = $cssClass ?? 'diff-removed';
                        $result .= '<del class="'.$class.'">'.$value.'</del>';
                    }
                    break;
            }
        }

        return $result;
    }
}
