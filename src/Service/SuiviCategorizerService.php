<?php

namespace App\Service;

use App\Dto\SuiviCategory;
use App\Entity\Suivi;
use App\Messenger\MessageHandler\NewSignalementCheckFileMessageHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class SuiviCategorizerService
{
    public function __construct(
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
    ) {
    }

    public function getSuiviCategory(Suivi $suivi): SuiviCategory
    {
        if (str_starts_with($this->htmlSanitizer->sanitize(NewSignalementCheckFileMessageHandler::SUIVI_ASK_DOCUMENTS_INTRO), $suivi->getDescription())) {
            $label = 'A faire';
            $labelClass = 'fr-badge--info';
            $title = 'Demande de documents';
            $icon = 'document.svg';
        } else {
            $label = 'Nouveau message';
            $labelClass = 'fr-badge--warning';
            $title = HtmlCleaner::clean($suivi->getDescription());
            $title = (strlen($title) > 50) ? substr($title, 0, 50).'...' : $title;
            $icon = 'mail-send.svg';
        }

        $category = new SuiviCategory(
            suivi: $suivi,
            label: $label,
            labelClass: $labelClass,
            title: $title,
            icon: $icon
        );

        return $category;
    }
}
