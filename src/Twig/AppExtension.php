<?php

namespace App\Twig;

use App\Entity\Enum\QualificationStatus;
use App\Service\Notification\NotificationCounter;
use App\Service\Signalement\QualificationStatusService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('status_to_css', [$this, 'getCssFromStatus']),
            new TwigFilter('image64', [$this, 'createBase64Image']),
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

    public function getFunctions(): array
    {
        return [
            new TwigFunction('count_notification', [NotificationCounter::class, 'countUnseenNotification']),
            new TwigFunction('can_see_nde_qualification', [QualificationStatusService::class, 'canSeenNDEQualification']),
            new TwigFunction('can_see_nde_edit_zone', [QualificationStatusService::class, 'canSeenNDEEditZone']),
        ];
    }

    public function createBase64Image(string $filename): string
    {
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
        $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;
        file_put_contents($tmpFilepath, file_get_contents($bucketFilepath));

        $type = pathinfo($tmpFilepath, \PATHINFO_EXTENSION);
        $data = base64_encode(file_get_contents($tmpFilepath));

        return "data:image/$type;base64,$data";
    }
}
