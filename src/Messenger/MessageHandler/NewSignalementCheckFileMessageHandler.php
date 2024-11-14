<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Enum\DocumentType;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Messenger\Message\NewSignalementCheckFileMessage;
use App\Repository\DesordreCritereRepository;
use App\Repository\SignalementRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NewSignalementCheckFileMessageHandler
{
    private const DESORDRES_CATEGORIES_WITH_PHOTOS = [
        'desordres_batiment_proprete' => 'desordres_batiment_proprete_photos',
        'desordres_batiment_nuisibles' => 'desordres_batiment_nuisibles_photos',
        'desordres_logement_humidite' => 'desordres_logement_humidite_photos',
        'desordres_logement_nuisibles' => 'desordres_logement_nuisibles_photos',
    ];

    private const DESORDRES_CRITERES_WITH_PHOTOS = [
        'desordres_batiment_isolation_infiltration_eau' => 'desordres_batiment_isolation_photos',
        'desordres_batiment_maintenance_petites_reparations' => 'desordres_batiment_maintenance_photos',
        'desordres_logement_chauffage_details_chauffage_dangereux' => 'desordres_logement_chauffage_details_chauffage_dangereux_photos',
        'desordres_logement_securite_sol_glissant' => 'desordres_logement_securite_photos',
        'desordres_logement_securite_balcons' => 'desordres_logement_securite_photos',
        'desordres_logement_securite_plomb' => 'desordres_logement_securite_photos',
        'desordres_logement_electricite_installation_dangereuse' => 'desordres_logement_electricite_installation_dangereuse_details_photos',
    ];

    public function __construct(
        private SignalementRepository $signalementRepository,
        private LoggerInterface $logger,
        private DesordreCritereRepository $desordreCritereRepository,
        private SuiviManager $suiviManager,
        protected Security $security,
    ) {
    }

    public function __invoke(NewSignalementCheckFileMessage $newSignalementCheckFileMessage): void
    {
        $this->logger->info('Start handling NewSignalementCheckFileMessage', [
            'signalementId' => $newSignalementCheckFileMessage->getSignalementId(),
        ]);

        $signalement = $this->signalementRepository->find(
            $newSignalementCheckFileMessage->getSignalementId()
        );

        $documents = '';
        if ($signalement->getTypeCompositionLogement()->getBailDpeBail() === 'oui'
                && !$this->hasDocumentType($signalement, DocumentType::SITUATION_FOYER_BAIL)) {
            $documents = 'le bail du logement';
        }
        if ($signalement->getTypeCompositionLogement()->getBailDpeEtatDesLieux() === 'oui'
                && !$this->hasDocumentType($signalement, DocumentType::SITUATION_FOYER_ETAT_DES_LIEUX)) {
            if (!empty($documents)) {
                $documents .= ', ';
            }
            $documents .= 'l\'état des lieux réalisé à l\'entrée dans le logement';
        }
        if ($signalement->getTypeCompositionLogement()->getBailDpeDpe() === 'oui'
                && !$this->hasDocumentType($signalement, DocumentType::SITUATION_FOYER_DPE)) {
            if (!empty($documents)) {
                $documents .= ', ';
            }
            $documents .= 'le diagnostic de performance énergétique du logement (DPE)';
        }

        $desordres = '';
        foreach (self::DESORDRES_CATEGORIES_WITH_PHOTOS as $desordreCategorieSlug => $desordreSlug) {
            $categorieLabel = $this->hasCritereFromCategorieSlug($signalement, $desordreCategorieSlug);
            if ($categorieLabel && !$this->hasPhotoForCritere($signalement, $desordreSlug)) {
                if (!empty($desordres)) {
                    $desordres .= ' / ';
                }
                $desordres .= $categorieLabel;
            }
        }

        foreach (self::DESORDRES_CRITERES_WITH_PHOTOS as $desordrePrecisionSlug => $desordreSlug) {
            $categorieLabel = $this->hasCritereFromCritereSlug($signalement, $desordrePrecisionSlug);
            if ($categorieLabel && !$this->hasPhotoForCritere($signalement, $desordreSlug)) {
                if (!empty($desordres)) {
                    $desordres .= ' / ';
                }
                $desordres .= $categorieLabel;
            }
        }

        $suiviId = null;
        if (!empty($documents) || !empty($desordres)) {
            $suivi = $this->createSuivi($signalement, $documents, $desordres);
            $suiviId = $suivi->getId();
        }

        $this->logger->info('NewSignalementCheckFileMessage handled successfully', [
            'signalementId' => $newSignalementCheckFileMessage->getSignalementId(),
            'suiviId' => $suiviId,
        ]);
    }

    private function hasDocumentType(Signalement $signalement, DocumentType $documentType): bool
    {
        foreach ($signalement->getFiles() as $file) {
            if ($file->getDocumentType() == $documentType) {
                return true;
            }
        }

        return false;
    }

    private function hasCritereFromCategorieSlug(Signalement $signalement, string $desordreCategorieSlug): ?string
    {
        $desordreCriteres = $this->desordreCritereRepository->findBy(
            ['slugCategorie' => $desordreCategorieSlug]
        );
        foreach ($desordreCriteres as $desordreCritere) {
            if ($signalement->hasDesordreCritere($desordreCritere)) {
                return $desordreCritere->getLabelCategorie();
            }
        }

        return null;
    }

    private function hasCritereFromCritereSlug(Signalement $signalement, string $desordreCritereSlug): ?string
    {
        $desordreCritere = $this->desordreCritereRepository->findOneBy(
            ['slugCritere' => $desordreCritereSlug]
        );
        if ($signalement->hasDesordreCritere($desordreCritere)) {
            return $desordreCritere->getLabelCategorie();
        }

        return null;
    }

    private function hasPhotoForCritere(Signalement $signalement, string $desordreSlug): bool
    {
        foreach ($signalement->getFiles() as $file) {
            if ($file->getDesordreSlug() == $desordreSlug) {
                return true;
            }
        }

        return false;
    }

    private function createSuivi(Signalement $signalement, string $documents, string $desordres): Suivi
    {
        $description = 'Bonjour,<br><br>';
        $description .= 'Vous avez un signalé un problème sur un logement.<br>';
        $description .= 'Votre dossier a bien été enregistré par nos services.<br><br>';
        $description .= 'Afin de nous aider à traiter au mieux votre dossier, veuillez nous fournir :<br>';
        if (!empty($documents)) {
            $description .= '- le ou les documents suivants : ' . $documents . '<br>';
        }
        if (!empty($desordres)) {
            $description .= '- des photos pour les désordres suivants : ' . $desordres . '<br>';
        }
        $description .= '<br>';
        $description .= 'Envoyez-nous un message en y ajoutant vos documents !<br>';
        $description .= 'Merci,<br>';
        $description .= 'L\'équipe Histologe';

        return $this->suiviManager->createSuivi(
            user: null,
            signalement: $signalement,
            params: [
                'type' => Suivi::TYPE_AUTO,
                'description' => $description,
            ],
            isPublic: true,
            flush: true
        );
    }
}
