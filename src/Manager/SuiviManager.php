<?php

namespace App\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\EventListener\SignalementUpdatedListener;
use App\Factory\SuiviFactory;
use App\Repository\DesordreCritereRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviManager extends Manager
{
    public function __construct(
        private readonly SuiviFactory $suiviFactory,
        protected ManagerRegistry $managerRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        protected SignalementUpdatedListener $signalementUpdatedListener,
        protected Security $security,
        private DesordreCritereRepository $desordreCritereRepository,
        string $entityName = Suivi::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createSuivi(
        ?User $user,
        Signalement $signalement,
        array $params,
        bool $isPublic = false,
        bool $flush = false,
        string $context = ''
    ): Suivi {
        $suivi = $this->suiviFactory->createInstanceFrom($user, $signalement, $params, $isPublic, $context);

        if ($flush) {
            $this->save($suivi);
        }

        return $suivi;
    }

    public function updateSuiviCreatedByUser(Suivi $suivi, User $user): Suivi
    {
        $suivi->setCreatedBy($user);

        $this->save($suivi);

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
                user: $user,
                signalement: $signalement,
                params: [
                    'type' => Suivi::TYPE_AUTO,
                    'description' => $description.$user->getNomComplet(),
                ],
                flush: true
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
            if ($nbDocs > 0) {
                $description .= sprintf(
                    '%s a ajouté %s %s de la visite du %s :',
                    $user->getPartner()->getNom(),
                    $nbDocs,
                    $nbDocs > 1 ? ' rapports de visite' : ' rapport de visite',
                    $intervention->getScheduledAt()->format('d/m/Y')
                );
            }
        }

        if (DocumentType::PHOTO_VISITE === $documentType) {
            $isVisibleUsager = true;
            if ($nbPhotos > 0) {
                $description .= sprintf(
                    '%s a ajouté %s %s de la visite du %s :',
                    $user->getPartner()->getNom(),
                    $nbPhotos,
                    $nbPhotos > 1 ? ' photos' : ' photo',
                    $intervention->getScheduledAt()->format('d/m/Y')
                );
            }
        }

        $descriptionList = [];
        foreach ($files as $file) {
            $fileUrl = $this->urlGenerator->generate(
                'show_uploaded_file',
                ['filename' => $file->getFilename()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ).'?t=___TOKEN___';

            $linkFile = '<li><a class="fr-link" target="_blank" href="'.$fileUrl.'">'.$file->getTitle().'</a>';
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

        $suivi = $this->suiviFactory->createInstanceFrom($user, $signalement);
        $suivi->setDescription(
            $description
            .'<ul>'
            .implode('', $descriptionList)
            .'</ul>'
        );

        $suivi->setIsPublic($isVisibleUsager);

        $suivi->setType(SUIVI::TYPE_AUTO);

        return $suivi;
    }
}
