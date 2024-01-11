<?php

namespace App\Twig;

use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\QualificationStatus;
use App\Service\Esabora\EsaboraPartnerTypeSubscription;
use App\Service\Files\ImageBase64Encoder;
use App\Service\Notification\NotificationCounter;
use App\Service\Signalement\Qualification\QualificationStatusService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_to_css', [$this, 'getCssFromStatus']),
            new TwigFilter('signalement_lien_declarant_occupant', [$this, 'getLabelLienDeclarantOccupant']),
            new TwigFilter('image64', [ImageBase64Encoder::class, 'encode']),
        ];
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

    public function getLabelLienDeclarantOccupant(string $lienDeclarantOccupant): string
    {
        if ('voisinage' == $lienDeclarantOccupant) {
            $lienDeclarantOccupant = 'voisin';
        }
        if (!empty(OccupantLink::getLabelList()[strtoupper($lienDeclarantOccupant)])) {
            return OccupantLink::getLabelList()[strtoupper($lienDeclarantOccupant)];
        }

        return '';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('count_notification', [NotificationCounter::class, 'countUnseenNotification']),
            new TwigFunction('can_see_nde_qualification', [QualificationStatusService::class, 'canSeenNDEQualification']),
            new TwigFunction('can_see_nde_edit_zone', [QualificationStatusService::class, 'canSeenNDEEditZone']),
            new TwigFunction('can_edit_esabora_credentials', [EsaboraPartnerTypeSubscription::class, 'isSubscribed']),
        ];
    }
}
