<?php

namespace App\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SuiviCreatedEvent;
use App\EventListener\SignalementUpdatedListener;
use App\Repository\DesordreCritereRepository;
use App\Service\Sanitizer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviManager extends Manager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SignalementUpdatedListener $signalementUpdatedListener,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security,
        private readonly DesordreCritereRepository $desordreCritereRepository,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        string $entityName = Suivi::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createSuivi(
        Signalement $signalement,
        string $description,
        int $type,
        bool $isPublic = false,
        ?User $user = null,
        ?\DateTimeImmutable $createdAt = null,
        ?string $context = null,
        bool $sendMail = true,
        bool $flush = true,
    ): Suivi {
        $suivi = (new Suivi())
            ->setCreatedBy($user)
            ->setSignalement($signalement)
            ->setDescription($this->htmlSanitizer->sanitize($description))
            ->setType($type)
            ->setIsPublic($isPublic)
            ->setContext($context)
            ->setSendMail($sendMail);
        if (!empty($createdAt)) {
            $suivi->setCreatedAt($createdAt);
        }
        if ($flush) {
            $this->save($suivi);
        } else {
            $this->persist($suivi);
        }
        $this->eventDispatcher->dispatch(new SuiviCreatedEvent($suivi), SuiviCreatedEvent::NAME);

        return $suivi;
    }

    public function addSuiviIfNeeded(
        Signalement $signalement,
        string $description,
    ): void {
        if ($this->signalementUpdatedListener->updateOccurred()) {
            /** @var User $user */
            $user = $this->security->getUser();
            $this->createSuivi(
                signalement: $signalement,
                description: $description.$user->getNomComplet(),
                type: Suivi::TYPE_AUTO,
                user: $user,
            );
        }
    }

    public function createInstanceForFilesSignalement(User $user, Signalement $signalement, array $files): Suivi
    {
        $nbDocs = 0;
        $nbPhotos = 0;
        /** @var ?DocumentType $documentType */
        $documentType = null;

        /** @var ?Intervention $intervention */
        $intervention = null;
        foreach ($files as $file) {
            if (File::FILE_TYPE_PHOTO === $file->getFileType()) {
                ++$nbPhotos;
            } else {
                ++$nbDocs;
            }
            $documentType = $file->getDocumentType();
            $intervention = $file->getIntervention();
        }
        $description = '';
        $isVisibleUsager = false;

        if (
            \array_key_exists($documentType?->value, DocumentType::getOrderedProcedureList())
            && null === $intervention
        ) {
            if ($nbDocs > 0) {
                $description .= $nbDocs;
                $description .= $nbDocs > 1 ? ' documents liés à la procédure ont été ajoutés' : ' document lié à la procédure a été ajouté';
                $description .= ' au signalement.';
            }
        }

        if (\array_key_exists($documentType?->value, DocumentType::getOrderedSituationList())) {
            $isVisibleUsager = true;
            if ($nbDocs > 0) {
                $description .= $nbDocs;
                $description .= $nbDocs > 1 ? ' documents ' : ' document ';
                $description .= 'sur la situation usager';
            }

            if ($nbPhotos > 0) {
                if ('' !== $description) {
                    $description .= ' et ';
                }
                $description .= $nbPhotos;
                $description .= $nbPhotos > 1 ? ' photos' : ' photo';
                if (null !== $signalement->getCreatedFrom()) {
                    $description .= ' concernant les désordres suivants';
                }
            }
            if (0 === $nbDocs && $nbPhotos > 1) {
                $description .= ' ont été ajoutées au signalement : ';
            } elseif ($nbDocs + $nbPhotos > 1) {
                $description .= ' ont été ajoutés au signalement : ';
            } elseif (1 === $nbPhotos) {
                $description .= ' a été ajoutée au signalement : ';
            }
        }

        if (DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $documentType && null !== $intervention) {
            $isVisibleUsager = true;
            $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
            if ($nbDocs > 0) {
                $description .= \sprintf(
                    '%s a ajouté %s %s de la visite du %s :',
                    $partner->getNom(),
                    $nbDocs,
                    $nbDocs > 1 ? ' rapports de visite' : ' rapport de visite',
                    $intervention->getScheduledAt()->format('d/m/Y')
                );
            }
        }

        if (DocumentType::PHOTO_VISITE === $documentType) {
            $isVisibleUsager = true;
            $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
            if ($nbPhotos > 0) {
                $description .= \sprintf(
                    '%s a ajouté %s %s de la visite du %s :',
                    $partner->getNom(),
                    $nbPhotos,
                    $nbPhotos > 1 ? ' photos' : ' photo',
                    $intervention->getScheduledAt()->format('d/m/Y')
                );
            }
        }

        $descriptionList = [];
        foreach ($files as $file) {
            $fileUrl = $this->urlGenerator->generate(
                'show_file',
                ['uuid' => $file->getUuid()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $linkFile = '<li><a class="fr-link" target="_blank" rel="noopener" href="'.$fileUrl.'">'.$file->getTitle().'</a>';
            if (DocumentType::PHOTO_SITUATION === $file->getDocumentType() && null !== $file->getDesordreSlug()) {
                $desordreCritere = $this->desordreCritereRepository->findOneBy(
                    ['slugCritere' => $file->getDesordreSlug()]
                );
                if (null !== $desordreCritere) {
                    $linkFile .= ' ('.$desordreCritere->getLabelCritere().')';
                }
            }
            $linkFile .= '</li>';
            $descriptionList[] = $linkFile;
        }
        $description .= '<ul>'.implode('', $descriptionList).'</ul>';

        return $this->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_AUTO,
            isPublic: $isVisibleUsager,
            user: $user,
            flush: false
        );
    }

    public static function buildDescriptionClotureSignalement($params): string
    {
        $motifSuivi = Sanitizer::sanitize($params['motif_suivi']);

        return \sprintf(
            'Le signalement a été fermé pour %s avec le motif suivant <br><strong>%s</strong><br><strong>Desc. : </strong>%s',
            $params['subject'],
            $params['motif_cloture']->label(),
            $motifSuivi
        );
    }

    public static function buildDescriptionAnswerAffectation(array $params): string
    {
        $description = '';
        if (isset($params['accept'])) {
            $description = 'Le signalement a été accepté';
        } elseif (isset($params['suivi'])) {
            $motifRejected = !empty($params['motifRefus']) ? $params['motifRefus']->label() : 'Non précisé';
            $commentaire = Sanitizer::sanitize($params['suivi']);
            $description = 'Le signalement a été refusé avec le motif suivant : '.$motifRejected.'.<br>Plus précisément :<br>'.$commentaire;
        }

        return $description;
    }
}
