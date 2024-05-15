<?php

namespace App\Twig;

use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\QualificationStatus;
use App\Service\Esabora\EsaboraPartnerTypeSubscription;
use App\Service\Files\ImageBase64Encoder;
use App\Service\ImageManipulationHandler;
use App\Service\Notification\NotificationCounter;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\UploadHandlerService;
use App\Utils\AttributeParser;
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
            new TwigFilter('truncate_filename', [$this, 'getTruncatedFilename']),
            new TwigFilter('clean_tagged_text', [$this, 'cleanTaggedText']),
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

    public function getTruncatedFilename(string $fileName, int $maxCharacters = 50): string
    {
        if (\strlen($fileName) > $maxCharacters) {
            $extensionLength = \strlen(pathinfo($fileName, \PATHINFO_EXTENSION));
            $fileNameLength = $maxCharacters - 4 - $extensionLength;
            $truncatedFileName = substr($fileName, 0, $fileNameLength);
            $truncatedFileName .= '....';
            $truncatedFileName .= pathinfo($fileName, \PATHINFO_EXTENSION);

            return $truncatedFileName;
        }

        return $fileName;
    }

    public function cleanTaggedText(?string $taggedText, string $tag, string $direction): string
    {
        if (null === $taggedText) {
            return '';
        }

        $parts = explode($tag, $taggedText);

        switch ($direction) {
            case 'left':
                return $parts[0];
            case 'right':
                return $parts[1];
            default:
                return $taggedText;
        }
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('count_notification', [NotificationCounter::class, 'countUnseenNotification']),
            new TwigFunction('can_see_nde_qualification', [QualificationStatusService::class, 'canSeenNDEQualification']), // TODO -> plus utilisé ?
            new TwigFunction('can_see_nde_edit_zone', [QualificationStatusService::class, 'canSeenNDEEditZone']),
            new TwigFunction('can_edit_esabora_credentials', [EsaboraPartnerTypeSubscription::class, 'isSubscribed']),
            new TwigFunction('show_label_facultatif', [AttributeParser::class, 'showLabelAsFacultatif']),
            new TwigFunction('get_accepted_mime_type', [$this, 'getAcceptedMimeTypes']),
            new TwigFunction('get_accepted_extensions', [$this, 'getAcceptedExtensions']),
        ];
    }

    public function getAcceptedMimeTypes(?string $type = 'document'): string
    {
        if ('document' === $type) {
            return implode(',', UploadHandlerService::DOCUMENT_MIME_TYPES);
        }

        return implode(',', ImageManipulationHandler::IMAGE_MIME_TYPES);
    }

    public function getAcceptedExtensions(?string $type = 'document'): string
    {
        if ('document' === $type) {
            $extensions = array_map('strtoupper', UploadHandlerService::DOCUMENT_EXTENSION);
        } else {
            $extensions = array_map('strtoupper', ImageManipulationHandler::IMAGE_EXTENSION);
        }

        if (1 === \count($extensions)) {
            return $extensions[0];
        }

        $allButLast = \array_slice($extensions, 0, -1);
        $last = end($extensions);
        $all = implode(', ', $allButLast).' ou '.$last;

        return $all;
    }
}
