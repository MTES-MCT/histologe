<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersMessagesNouveauxTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_NOUVEAUX;

    public function __construct(private readonly Security $security, private readonly TabDataManagerInterface $TabDataManagerInterface)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $result = $this->TabDataManagerInterface->getMessagesUsagersNouveauxMessages(
            $this->tabQueryParameters
        );

        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        $filters = [
            ...$tabBody->getFilters(),
            'isNouveauMessage' => 'oui',
            'showMySignalementsOnly' => '1' === $this->tabQueryParameters->mesDossiersMessagesUsagers ? 'oui' : null,
            'sortBy' => 'lastSuiviAt',
            'direction' => $this->tabQueryParameters->orderBy ?? 'ASC',
        ];
        $tabBody->setFilters($filters);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_messages_usagers/_body_dossier_messages_nouveaux.html.twig');
    }
}
