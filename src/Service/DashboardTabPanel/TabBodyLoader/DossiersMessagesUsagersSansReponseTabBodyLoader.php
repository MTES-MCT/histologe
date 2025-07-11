<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersMessagesUsagersSansReponseTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_USAGERS_SANS_REPONSE;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $tabBody->setData($this->tabDataManager->getMessagesUsagersMessagesSansReponse());
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_messages_usagers/_body_dossier_messages_usagers_sans_reponse.html.twig');
    }
}
