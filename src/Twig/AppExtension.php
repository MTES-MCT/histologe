<?php

namespace App\Twig;

use App\Command\FixEmailAddressesCommand;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\QualificationStatus;
use App\Entity\File;
use App\Service\Files\ImageBase64Encoder;
use App\Service\Notification\NotificationCounter;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use App\Utils\AttributeParser;
use App\Validator\EmailFormatValidator;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly TimezoneProvider $timezoneProvider)
    {
    }

    public function getGlobals(): array
    {
        return ['territory_timezone' => $this->timezoneProvider->getTimezone()];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('date', [$this, 'customDateFilter'], ['is_safe' => ['html']]),
            new TwigFilter('status_to_css', [$this, 'getCssFromStatus']),
            new TwigFilter('signalement_lien_declarant_occupant', [$this, 'getLabelLienDeclarantOccupant']),
            new TwigFilter('image64', [ImageBase64Encoder::class, 'encode']),
            new TwigFilter('truncate_filename', [$this, 'getTruncatedFilename']),
            new TwigFilter('clean_tagged_text', [$this, 'cleanTaggedText']),
        ];
    }

    /**
     * @throws \Exception
     */
    public function customDateFilter(string|\DateTimeImmutable|\DateTime $dateTime, string $format = 'F j, Y'): string
    {
        if ($dateTime instanceof \DateTimeInterface) {
            return $dateTime->setTimezone($this->timezoneProvider->getDateTimezone())->format($format);
        }

        return (new \DateTimeImmutable($dateTime))->setTimezone($this->timezoneProvider->getDateTimezone())->format($format);
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
            new TwigFunction('can_see_nde_edit_zone', [QualificationStatusService::class, 'canSeenNDEEditZone']),
            new TwigFunction('show_label_facultatif', [AttributeParser::class, 'showLabelAsFacultatif']),
            new TwigFunction('get_accepted_mime_type', [$this, 'getAcceptedMimeTypes']),
            new TwigFunction('get_accepted_extensions', [UploadHandlerService::class, 'getAcceptedExtensions']),
            new TwigFunction('show_email_alert', [$this, 'showEmailAlert']),
        ];
    }

    public function getAcceptedMimeTypes(?string $type = 'document'): string
    {
        if ('document' === $type) {
            return implode(',', File::DOCUMENT_MIME_TYPES);
        }

        return implode(',', File::IMAGE_MIME_TYPES);
    }

    public function showEmailAlert(?string $emailAddress): bool
    {
        return !EmailFormatValidator::validate($emailAddress) || FixEmailAddressesCommand::EMAIL_HISTOLOGE_INCONNU === $emailAddress;
    }
}
