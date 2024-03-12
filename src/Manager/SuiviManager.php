<?php

namespace App\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\EventListener\SignalementUpdatedListener;
use App\Factory\SuiviFactory;
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
        $isRapportDeVisite = false;
        $isPhotoDeVisite = false;
        $nbDocs = 0;
        $nbPhotos = 0;
        foreach ($files as $file) {
            if (File::FILE_TYPE_PHOTO === $file->getFileType()) {
                ++$nbPhotos;
            } else {
                ++$nbDocs;
            }

            if (DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $file->getDocumentType()) {
                $isRapportDeVisite = true;
            }
            if (DocumentType::PHOTO_VISITE === $file->getDocumentType()) {
                $isPhotoDeVisite = true;
            }
        }
        $description = '';
        if ($nbDocs > 0) {
            $description .= $nbDocs;
            if ($isRapportDeVisite) {
                $description .= $nbDocs > 1 ? ' rapports de visite' : ' rapport de visite';
            } else {
                $description .= $nbDocs > 1 ? ' documents partenaires' : ' document partenaire';
            }
        }
        if ($nbPhotos > 0) {
            if ('' !== $description) {
                $description .= ' et ';
            }
            $description .= $nbPhotos;
            $description .= $nbPhotos > 1 ? ' photos' : ' photo';
            if ($isPhotoDeVisite) {
                $description .= ' de visite';
            }
        }
        if ($nbDocs + $nbPhotos > 1) {
            $description .= ' ont été ajoutés au signalement : ';
        } else {
            $description .= ' a été ajouté au signalement : ';
        }
        $descriptionList = [];
        foreach ($files as $file) {
            $fileUrl = $this->urlGenerator->generate('show_uploaded_file', ['folder' => '_up', 'filename' => $file->getFilename()]);
            $descriptionList[] = '<li><a class="fr-link" target="_blank" href="'.$fileUrl.'">'.$file->getTitle().'</a></li>';
        }

        $suivi = $this->suiviFactory->createInstanceFrom($user, $signalement);
        $suivi->setDescription(
            $description
            .'<ul>'
            .implode('', $descriptionList)
            .'</ul>'
        );

        if ($isRapportDeVisite || $isPhotoDeVisite) {
            $suivi->setIsPublic(true);
        }

        $suivi->setType(SUIVI::TYPE_AUTO);

        return $suivi;
    }
}
