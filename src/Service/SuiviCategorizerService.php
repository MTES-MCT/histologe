<?php

namespace App\Service;

use App\Dto\SuiviCategory as SuiviCategoryDto;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;

class SuiviCategorizerService
{
    public function __construct(
    ) {
    }

    public function getSuiviCategory(Suivi $suivi): SuiviCategoryDto
    {
        switch ($suivi->getCategory()) {
            case SuiviCategory::ASK_DOCUMENT:
                $label = 'A faire';
                $labelClass = 'fr-badge--info';
                $title = 'Demande de documents';
                $icon = 'document.svg';
                break;
            case SuiviCategory::SIGNALEMENT_IS_ACTIVE:
                $label = 'Nouveauté';
                $labelClass = 'fr-badge--success';
                $title = 'Votre dossier est validé';
                $icon = 'success.svg';
                break;
            case SuiviCategory::AFFECTATION_IS_ACCEPTED:
                $label = 'Nouveauté';
                $labelClass = 'fr-badge--success';
                $title = 'Votre dossier est pris en charge';
                $icon = 'success.svg';
                break;
            case SuiviCategory::NEW_DOCUMENT:
                $label = 'Nouveauté';
                $labelClass = 'fr-badge--info';
                $title = 'Nouveaux documents disponibles';
                $icon = 'document.svg';
                break;
            case SuiviCategory::INTERVENTION_IS_PLANNED:
                $label = 'Nouveauté';
                $labelClass = 'fr-badge--success';
                $title = 'Visite du logement programmée';
                $icon = 'house.svg';
                break;
            case SuiviCategory::INTERVENTION_IS_CANCELED:
                $label = 'Important';
                $labelClass = 'fr-badge--error';
                $title = 'Visite annulée';
                $icon = 'error.svg';
                break;
            case SuiviCategory::INTERVENTION_HAS_CONCLUSION:
                $label = 'Nouveauté';
                $labelClass = 'fr-badge--success';
                $title = 'Conclusion de visite disponible';
                $icon = 'conclusion.svg';
                break;
            case SuiviCategory::INTERVENTION_IS_RESCHEDULED:
                $label = 'Nouveauté ';
                $labelClass = 'fr-badge--warning';
                $title = 'Changement de la date de visite !';
                $icon = 'notification.svg';
                break;
            case SuiviCategory::SIGNALEMENT_IS_CLOSED:
                $label = 'Important';
                $labelClass = 'fr-badge--error';
                $title = 'Fermeture de votre dossier';
                $icon = 'conclusion.svg';
                break;
            default:
                $label = 'Nouveau message';
                $labelClass = 'fr-badge--warning';
                $title = HtmlCleaner::clean($suivi->getDescription());
                $title = (strlen($title) > 50) ? substr($title, 0, 50).'...' : $title;
                $icon = 'mail-send.svg';
        }

        return new SuiviCategoryDto(
            suivi: $suivi,
            label: $label,
            labelClass: $labelClass,
            title: $title,
            icon: $icon
        );
    }
}
