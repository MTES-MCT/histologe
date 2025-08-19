<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersMessagesApresFermetureTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_APRES_FERMETURE;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);

        $result = $this->tabDataManager->getMessagesUsagersMessageApresFermeture(
            $this->tabQueryParameters
        );

        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        $filters = [
            ...$tabBody->getFilters(),
            'isMessagePostCloture' => 'oui',
            'showMySignalementsOnly' => '1' === $this->tabQueryParameters->mesDossiersMessagesUsagers ? 'oui' : null,
            'sortBy' => 'lastSuiviAt',
            'direction' => $this->tabQueryParameters->orderBy ?? 'ASC',
        ];
        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_messages_usagers/_body_dossier_messages_apres_fermeture.html.twig');
    }
}
