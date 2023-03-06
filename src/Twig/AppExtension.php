<?php

namespace App\Twig;

use App\Entity\Enum\QualificationStatus;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public const PATTERN_REPLACE_PHONE_FR = '/^\+?33|\|?0033|\|+33 (0)|\D/';

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_phone', [$this, 'formatPhone']),
            new TwigFilter('array_to_string', [$this, 'formatArrayToString']),
            new TwigFilter('reference_sortable', [$this, 'formatSortableReference']),
            new TwigFilter('status_to_css', [$this, 'getCssFromStatus']),
        ];
    }

    public function formatPhone(?string $value): ?string
    {
        $value = preg_replace(self::PATTERN_REPLACE_PHONE_FR, '', $value);

        if (9 === \strlen($value)) {
            $value = str_pad($value, 10, '0', \STR_PAD_LEFT);
        }

        return trim(chunk_split($value, 2, ' '));
    }

    public function formatArrayToString(?array $listData): string
    {
        $str = '';
        foreach ($listData as $index => $data) {
            if ('' != $str) {
                $str .= ', ';
            }
            $str .= $data;
        }

        return $str;
    }

    public function formatSortableReference(?string $reference = ''): string
    {
        if (empty($reference)) {
            return '';
        }

        $referenceSplit = explode('-', $reference);
        if (\count($referenceSplit) < 2) {
            return $reference;
        }

        return $referenceSplit[0].'-'.str_pad($referenceSplit[1], 10, 0, \STR_PAD_LEFT);
    }

    public function getCssFromStatus(QualificationStatus $qualificationStatus): string
    {
        $css = 'fr-badge fr-badge--sm';
        if (QualificationStatus::NDE_AVEREE === $qualificationStatus) {
            $css .= ' fr-badge--error';
        } elseif (QualificationStatus::NDE_CHECK === $qualificationStatus) {
            $css .= ' fr-badge--info';
        } elseif (QualificationStatus::NDE_OK === $qualificationStatus) {
            $css .= ' fr-badge--success';
        }

        return $css;
    }
}
