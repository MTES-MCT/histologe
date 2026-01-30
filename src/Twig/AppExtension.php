<?php

namespace App\Twig;

use App\Controller\FileController;
use App\Entity\Commune;
use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;
use App\Entity\Enum\QualificationStatus;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\CommuneRepository;
use App\Service\EmailAlertChecker;
use App\Service\Files\ImageBase64Encoder;
use App\Service\Notification\NotificationCounter;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use App\Service\UserAvatar;
use App\Utils\AttributeParser;
use App\Validator\EmailFormatValidator;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly TimezoneProvider $timezoneProvider,
        private readonly UrlSignerInterface $urlSigner,
        private readonly CommuneRepository $communeRepository,
    ) {
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
            new TwigFilter('display_signalement_info_bailleur', [$this, 'isDisplaySignalementInfoBailleur']),
            new TwigFilter('image64', [ImageBase64Encoder::class, 'encode']),
            new TwigFilter('truncate_filename', [$this, 'getTruncatedFilename']),
            new TwigFilter('clean_tagged_text', [$this, 'cleanTaggedText']),
            new TwigFilter('phone', [$this, 'formatPhone']),
            new TwigFilter('badge_class', [$this, 'getBadgeClass']),
            new TwigFilter('badge_relance_class', [$this, 'getRelanceBadgeClass']),
        ];
    }

    public function getBadgeClass(?int $days): string
    {
        return match (true) {
            $days > 365 => 'fr-badge--error',
            $days >= 181 => 'fr-badge--warning',
            $days >= 91 => 'fr-badge--new',
            null === $days => 'fr-badge--info',
            default => 'fr-badge--success',
        };
    }

    public function getRelanceBadgeClass(int $count): string
    {
        return match (true) {
            $count > 10 => 'fr-badge--error',
            $count >= 4 => 'fr-badge--warning',
            default => 'fr-badge--new',
        };
    }

    /**
     * @throws \Exception
     */
    public function customDateFilter(
        string|\DateTimeImmutable|\DateTime|null $dateTime,
        string $format = 'F j, Y H:i',
        ?string $timezone = null,
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

    public function isDisplaySignalementInfoBailleur(Signalement $signalement): bool
    {
        return ProfileDeclarant::LOCATAIRE === $signalement->getProfileDeclarant()
            || (
                (
                    ProfileDeclarant::SERVICE_SECOURS === $signalement->getProfileDeclarant()
                    || ProfileDeclarant::TIERS_PARTICULIER === $signalement->getProfileDeclarant()
                    || ProfileDeclarant::TIERS_PRO === $signalement->getProfileDeclarant()
                )
                && ProfileOccupant::LOCATAIRE === $signalement->getProfileOccupant()
            );
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

    /**
     * @param non-empty-string $tag
     */
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

    public function formatPhone(?string $phoneNumber): string
    {
        if (null === $phoneNumber) {
            return '';
        }

        if (str_starts_with($phoneNumber, '+33')) {
            return preg_replace("/(\d{2})(\d{1})(\d{2})(\d{2})(\d{2})(\d{2})/i", '$1 $2 $3 $4 $5 $6', $phoneNumber);
        }

        return $phoneNumber;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('count_notification', [NotificationCounter::class, 'countUnseenNotification']),
            new TwigFunction('show_label_facultatif', [AttributeParser::class, 'showLabelAsFacultatif']),
            new TwigFunction('get_accepted_mime_type', [$this, 'getAcceptedMimeTypes']),
            new TwigFunction('get_accepted_extensions', [UploadHandlerService::class, 'getAcceptedExtensions']),
            new TwigFunction('show_email_alert', [EmailFormatValidator::class, 'isInvalidEmail']),
            new TwigFunction('show_email_usager_alert_brevo', [EmailAlertChecker::class, 'hasUsagerEmailAlert']),
            new TwigFunction('show_email_partner_alert_brevo', [EmailAlertChecker::class, 'hasPartnerEmailAlert']),
            new TwigFunction('user_avatar_or_placeholder', [UserAvatar::class, 'userAvatarOrPlaceholder'], ['is_safe' => ['html']]),
            new TwigFunction('singular_or_plural', [$this, 'displaySingularOrPlural']),
            new TwigFunction('transform_suivi_description', [$this, 'transformSuiviDescription']),
            new TwigFunction('sign_url', [$this, 'signUrl']),
            new TwigFunction('get_communes_by_insee', [$this, 'getCommunesByInsee']),
            new TwigFunction('root_domain', [$this, 'extractRootDomain']),
        ];
    }

    public function extractRootDomain(string $host): string
    {
        // Retire le premier sous-domaine s'il est présent
        $parts = explode('.', $host);

        // Si c'est un domaine à un seul niveau (comme "localhost"), on le retourne tel quel
        if (1 === count($parts)) {
            return $host;
        }

        // Sinon on retire la première partie (le sous-domaine)
        return implode('.', array_slice($parts, 1));
    }

    public function getAcceptedMimeTypes(?string $type = null): string
    {
        if ('resizable' === $type) {
            return implode(',', File::RESIZABLE_MIME_TYPES);
        } elseif ('photo' === $type) {
            return implode(',', File::IMAGE_MIME_TYPES);
        }

        return implode(',', File::DOCUMENT_MIME_TYPES);
    }

    public function displaySingularOrPlural(?int $count, string $strIfSingular, string $strIfPlural): string
    {
        if (empty($count)) {
            $count = 0;
        }
        if ($count > 1) {
            return $count.' '.$strIfPlural;
        }

        return $count.' '.$strIfSingular;
    }

    public function transformSuiviDescription(Suivi $suivi): string
    {
        $content = $suivi->getDescription();
        $content = str_replace('&amp;t&#61;___TOKEN___', '/'.$suivi->getSignalement()->getUuid(), $content);
        $content = str_replace('?t&#61;___TOKEN___', '/'.$suivi->getSignalement()->getUuid(), $content);
        $content = str_replace('?folder&#61;_up', '/'.$suivi->getSignalement()->getUuid().'?variant=resize', $content);

        return $content;
    }

    public function signUrl(string $url): string
    {
        return $this->urlSigner->sign($url, FileController::SIGNATURE_VALIDITY_DURATION);
    }

    /**
     * Récupère les communes distinctes (par code INSEE) à partir d'un tableau de codes INSEE.
     *
     * @param array<int, string> $codesInsee
     *
     * @return array<int, Commune>
     */
    public function getCommunesByInsee(array $codesInsee): array
    {
        return $this->communeRepository->findDistinctCommuneCodesInseeForCodeInseeList($codesInsee);
    }
}
