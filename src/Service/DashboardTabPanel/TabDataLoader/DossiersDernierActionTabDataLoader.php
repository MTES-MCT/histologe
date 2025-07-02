<?php

namespace App\Service\DashboardTabPanel\TabDataLoader;

use App\Service\DashboardTabPanel\TabData;
use App\Service\DashboardTabPanel\TabDataLoaderInterface;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDataType;

readonly class DossiersDernierActionTabDataLoader implements TabDataLoaderInterface
{
    public function __construct(private TabDataManager $tabDataManager)
    {
    }

    public function load(TabData $tabData): void
    {
        $tabData->setData([
            'data' => $this->tabDataManager->getDernierActionDossiers(),
            'tiles' => [
                [
                    'icon_path' => 'img/picto-dsfr/warning.svg',
                    'title' => 'Comptes bientôt archivés',
                    'badge_class' => 'fr-badge--error',
                    'badge_label' => '5 Comptes',
                ],
                [
                    'icon_path' => 'img/picto-dsfr/notification.svg',
                    'title' => 'Partenaires non notifiables',
                    'badge_class' => 'fr-badge--info',
                    'badge_label' => '24 Partenaires',
                ],
            ],
        ]
        );
        $tabData->setTemplate('back/dashboard/tabs/data/accueil/_data_derniere_action_dossiers.html.twig');
    }

    public function supports(string $type): bool
    {
        return TabDataType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS === $type;
    }
}
