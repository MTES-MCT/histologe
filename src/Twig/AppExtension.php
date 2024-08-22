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
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly TimezoneProvider $timezoneProvider,
        private readonly ParameterBagInterface $parameterBag,
        private readonly FilesystemOperator $fileStorage,
    ){
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
    public function customDateFilter(
        string|\DateTimeImmutable|\DateTime|null $dateTime,
        string $format = 'F j, Y H:i',
        ?string $timezone = null
    ): ?string {
        if (null === $dateTime) {
            return null;
        }
        $dateTimeZone = null !== $timezone ? new \DateTimeZone($timezone) : $this->timezoneProvider->getDateTimezone();
        if ($dateTime instanceof \DateTimeInterface) {
            return $dateTime->setTimezone($dateTimeZone)->format($format);
        }

        if (is_numeric($dateTime)) { // is a timestamp
            $dateTime = (new \DateTimeImmutable())
                ->setTimestamp((int) $dateTime)
                ->setTimezone($dateTimeZone);
        } else { // is string date
            $dateTime = (new \DateTimeImmutable($dateTime))->setTimezone($dateTimeZone);
        }

        return $dateTime->format($format);
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
            new TwigFunction('user_avatar_or_placeholder', [$this, 'userAvatarOrPlaceholder'], ['is_safe' => ['html']]),
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

    public function userAvatarOrPlaceholder($user, $size = 74): string
    {
        $zipCode = $user->getTerritory() ? substr($user->getTerritory()->getZip(), 0, 2) : 'SA';

        if ($user->getAvatarFilename() && $this->fileStorage->fileExists($user->getAvatarFilename())) {
            $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$user->getAvatarFilename();

            $type = pathinfo($bucketFilepath, \PATHINFO_EXTENSION);

            // try {
            $data = file_get_contents($bucketFilepath);
            $data64 = base64_encode($data);

            $src = "data:image/$type;base64,$data64";
            // } catch (\Throwable $exception) {
            //     $this->logger->error($exception->getMessage());
            // }

            return sprintf(
                '<img src="%s" alt="Avatar de l\'utilisateur" class="avatar-image avatar-%s">',
                $src,
                $size
            );
        }

        return sprintf(
            '<span class="avatar-placeholder avatar-%s">%s</span>',
            $size,
            $zipCode
        );
    }
}
