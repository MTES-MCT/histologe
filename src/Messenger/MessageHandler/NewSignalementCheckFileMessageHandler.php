<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Enum\DocumentType;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Messenger\Message\NewSignalementCheckFileMessage;
use App\Repository\DesordreCritereRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NewSignalementCheckFileMessageHandler
{
    private const array DESORDRES_CATEGORIES_WITH_PHOTOS = [
        'desordres_batiment_proprete',
        'desordres_batiment_nuisibles',
        'desordres_batiment_isolation',
        'desordres_batiment_maintenance',
        'desordres_batiment_securite',
        'desordres_logement_humidite',
        'desordres_logement_nuisibles',
        'desordres_logement_securite',
    ];

    private const array DESORDRES_CRITERES_WITH_PHOTOS = [
        'desordres_logement_chauffage_details_chauffage_dangereux',
        'desordres_logement_electricite_installation_dangereuse',
    ];

    public ?Suivi $suivi;
    public ?string $description;

    public function __construct(
        private SignalementRepository $signalementRepository,
        private UserRepository $userRepository,
        private DesordreCritereRepository $desordreCritereRepository,
        private LoggerInterface $logger,
        private SuiviManager $suiviManager,
        private ParameterBagInterface $parameterBag,
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

        $documents = $this->getMissingDocumentsString($signalement);

        $desordres = $this->getMissingDesordresPhotosString($signalement);

        $this->suivi = null;
        if (!empty($documents) || !empty($desordres)) {
            $this->suivi = $this->createSuivi($signalement, $documents, $desordres);
        }

        $this->logger->info('NewSignalementCheckFileMessage handled successfully', [
            'signalementId' => $newSignalementCheckFileMessage->getSignalementId(),
            'suiviId' => $this->suivi ? $this->suivi->getId() : null,
        ]);
    }

    public function getMissingDocumentsString(Signalement $signalement): string
    {
        $documents = '';
        if ($signalement->getTypeCompositionLogement()) {
            if ('oui' === $signalement->getTypeCompositionLogement()->getBailDpeBail()
                    && !$this->hasDocumentType($signalement, DocumentType::SITUATION_FOYER_BAIL)) {
                $documents = 'le bail du logement';
            }
            if ('oui' === $signalement->getTypeCompositionLogement()->getBailDpeEtatDesLieux()
                    && !$this->hasDocumentType($signalement, DocumentType::SITUATION_FOYER_ETAT_DES_LIEUX)) {
                if (!empty($documents)) {
                    $documents .= ', ';
                }
                $documents .= 'l\'état des lieux réalisé à l\'entrée dans le logement';
            }
            if ('oui' === $signalement->getTypeCompositionLogement()->getBailDpeDpe()
                    && !$this->hasDocumentType($signalement, DocumentType::SITUATION_FOYER_DPE)) {
                if (!empty($documents)) {
                    $documents .= ', ';
                }
                $documents .= 'le diagnostic de performance énergétique du logement (DPE)';
            }
        }

        return $documents;
    }

    public function getMissingDesordresPhotosString(Signalement $signalement): string
    {
        $desordres = '';
        $signalementDesordreCategorieSlugs = $signalement->getDesordreCategorieSlugs();
        foreach (self::DESORDRES_CATEGORIES_WITH_PHOTOS as $desordreCategorieSlug) {
            if (!in_array($desordreCategorieSlug, $signalementDesordreCategorieSlugs)) {
                continue;
            }
            $categorieLabel = $this->getCategorieLabelFromCategorieSlug($desordreCategorieSlug);
            if ($categorieLabel && !$this->hasPhotoForCritere($signalement, $desordreCategorieSlug)) {
                if (!empty($desordres)) {
                    $desordres .= ' / ';
                }
                $desordres .= $categorieLabel;
            }
        }

        $signalementDesordrePrecisionSlugs = $signalement->getDesordrePrecisionSlugs();
        foreach (self::DESORDRES_CRITERES_WITH_PHOTOS as $desordrePrecisionSlug) {
            if (!in_array($desordrePrecisionSlug, $signalementDesordrePrecisionSlugs)) {
                continue;
            }
            $categorieLabel = $this->getCategorieLabelFromCritereSlug($desordrePrecisionSlug);
            if ($categorieLabel && !$this->hasPhotoForCritere($signalement, $desordrePrecisionSlug)) {
                if (!empty($desordres)) {
                    $desordres .= ' / ';
                }
                $desordres .= $categorieLabel;
            }
        }

        return $desordres;
    }

    private function hasDocumentType(Signalement $signalement, DocumentType $documentType): bool
    {
        foreach ($signalement->getFiles() as $file) {
            if ($file->getDocumentType() === $documentType) {
                return true;
            }
        }

        return false;
    }

    private function getCategorieLabelFromCategorieSlug(string $desordreCategorieSlug): ?string
    {
        $desordresCritere = $this->desordreCritereRepository->findBy(
            ['slugCategorie' => $desordreCategorieSlug]
        );
        $desordreCritere = $desordresCritere[0];
        if ($desordreCritere) {
            return $desordreCritere->getLabelCategorie();
        }

        return null;
    }

    private function getCategorieLabelFromCritereSlug(string $desordreCritereSlug): ?string
    {
        $desordreCritere = $this->desordreCritereRepository->findOneBy(
            ['slugCritere' => $desordreCritereSlug]
        );
        if ($desordreCritere) {
            return $desordreCritere->getLabelCategorie();
        }

        return null;
    }

    private function hasPhotoForCritere(Signalement $signalement, string $desordreSlug): bool
    {
        foreach ($signalement->getFiles() as $file) {
            if ($file->getDesordreSlug() === $desordreSlug) {
                return true;
            }
        }

        return false;
    }

    private function createSuivi(Signalement $signalement, string $documents, string $desordres): Suivi
    {
        $this->description = 'Bonjour,<br><br>';
        $this->description .= 'Vous avez un signalé un problème sur un logement.<br>';
        $this->description .= 'Votre dossier a bien été enregistré par nos services.<br><br>';
        $this->description .= 'Afin de nous aider à traiter au mieux votre dossier, veuillez nous fournir :<br>';
        if (!empty($documents)) {
            $this->description .= '- le ou les documents suivants : '.$documents.'<br>';
        }
        if (!empty($desordres)) {
            $this->description .= '- des photos pour les désordres suivants : '.$desordres.'<br>';
        }
        $this->description .= '<br>';
        $this->description .= 'Envoyez-nous un message en y ajoutant vos documents !<br>';
        $this->description .= 'Merci,<br>';
        $this->description .= 'L\'équipe Histologe';

        $userAdmin = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);

        return $this->suiviManager->createSuivi(
            user: $userAdmin,
            signalement: $signalement,
            params: [
                'type' => Suivi::TYPE_AUTO,
                'description' => $this->description,
            ],
            isPublic: true,
            context: Suivi::CONTEXT_NOTIFY_USAGER_ONLY,
            flush: true,
        );
    }
}
