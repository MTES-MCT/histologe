<?php

namespace App\Service;

use App\Dto\SuiviCategory as SuiviCategoryDto;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;

class SuiviCategoryMapper
{
    private const array SUIVI_CATEGORIES_CONFIGURATION = [
        SuiviCategory::ASK_DOCUMENT->name => [
            'label' => 'A faire',
            'labelClass' => 'fr-badge--info',
            'title' => 'Demande de documents',
            'icon' => 'document.svg',
        ],
        SuiviCategory::SIGNALEMENT_IS_ACTIVE->name => [
            'label' => 'Nouveauté',
            'labelClass' => 'fr-badge--success',
            'title' => 'Votre dossier est validé',
            'icon' => 'success.svg',
        ],
        SuiviCategory::AFFECTATION_IS_ACCEPTED->name => [
            'label' => 'Nouveauté',
            'labelClass' => 'fr-badge--success',
            'title' => 'Votre dossier est pris en charge',
            'icon' => 'success.svg',
        ],
        SuiviCategory::NEW_DOCUMENT->name => [
            'label' => 'Nouveauté',
            'labelClass' => 'fr-badge--info',
            'title' => 'Nouveaux documents disponibles',
            'icon' => 'document.svg',
        ],
        SuiviCategory::INTERVENTION_IS_CREATED->name => [
            'label' => 'Nouveauté',
            'labelClass' => 'fr-badge--success',
            'title' => 'Visite du logement programmée',
            'icon' => 'house.svg',
        ],
        SuiviCategory::INTERVENTION_IS_CANCELED->name => [
            'label' => 'Important',
            'labelClass' => 'fr-badge--error',
            'title' => 'Visite annulée',
            'icon' => 'error.svg',
        ],
        SuiviCategory::INTERVENTION_HAS_CONCLUSION->name => [
            'label' => 'Nouveauté',
            'labelClass' => 'fr-badge--success',
            'title' => 'Conclusion de visite disponible',
            'icon' => 'conclusion.svg',
        ],
        SuiviCategory::INTERVENTION_IS_RESCHEDULED->name => [
            'label' => 'Nouveauté',
            'labelClass' => 'fr-badge--warning',
            'title' => 'Changement de la date de visite !',
            'icon' => 'notification.svg',
        ],
        SuiviCategory::SIGNALEMENT_IS_CLOSED->name => [
            'label' => 'Important',
            'labelClass' => 'fr-badge--error',
            'title' => 'Fermeture de votre dossier',
            'icon' => 'conclusion.svg',
        ],
        SuiviCategory::DEMANDE_ABANDON_PROCEDURE->name => [
            'label' => 'Important',
            'labelClass' => 'fr-badge--warning',
            'title' => 'Votre demande a été enregistrée',
            'icon' => 'success.svg',
        ],
    ];

    public function __construct(
    ) {
    }

    public function mapFromSuivi(Suivi $suivi): SuiviCategoryDto
    {
        if ($suivi->getCategory() && isset(self::SUIVI_CATEGORIES_CONFIGURATION[$suivi->getCategory()->name])) {
            $configuration = self::SUIVI_CATEGORIES_CONFIGURATION[$suivi->getCategory()->name];
        } else {
            $title = HtmlCleaner::clean($suivi->getDescription());
            $title = (mb_strlen($title) > 50) ? mb_substr($title, 0, 50).'...' : $title;
            $configuration = [
                'label' => 'Nouveau message',
                'labelClass' => 'fr-badge--warning',
                'title' => $title,
                'icon' => 'mail-send.svg',
            ];
        }

        return new SuiviCategoryDto(
            suivi: $suivi,
            label: $configuration['label'],
            labelClass: $configuration['labelClass'],
            title: $configuration['title'],
            icon: $configuration['icon'],
            description: $this->getSuiviCategoryDescription($suivi),
        );
    }

    private function getSuiviCategoryDescription(Suivi $suivi): ?string
    {
        if (
            SuiviCategory::SIGNALEMENT_IS_CLOSED === $suivi->getCategory()
            && SignalementStatus::CLOSED === $suivi->getSignalement()->getStatut()
            && $suivi->getSignalement()->getClosedAt()
        ) {
            return 'Si nécessaire, vous pouvez envoyer un dernier message avant le '.$suivi->getSignalement()->getClosedAt()->modify('+30 days')->format('d/m/Y').'.';
        }

        return null;
    }
}
