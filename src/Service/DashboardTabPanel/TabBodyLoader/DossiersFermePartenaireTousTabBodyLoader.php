<?php

namespace App\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Enum\SignalementStatus;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersFermePartenaireTousTabBodyLoader extends AbstractTabBodyLoader
{
    protected ?string $tabBodyType = TabBodyType::TAB_DATA_TYPE_DOSSIERS_FERME_PARTENAIRE_TOUS;

    public function __construct(private readonly Security $security, private readonly TabDataManager $tabDataManager)
    {
        parent::__construct($this->security);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function load(TabBody $tabBody): void
    {
        parent::load($tabBody);
        $result = $this->tabDataManager->getDossiersFermePartenaireTous($this->tabQueryParameters);

        $filters = [
            ...$tabBody->getFilters(),
            'statusAffectation' => 'cloture_tous_partenaire',
            'status' => str_replace(' ', '_', SignalementStatus::ACTIVE->label()),
        ];

        $this->tabQueryParameters->sortBy = $filters['sortBy'] = 'createdAt';

        $tabBody->setFilters($filters);
        $tabBody->setData($result->dossiers);
        $tabBody->setCount($result->count);
        $tabBody->setTemplate('back/dashboard/tabs/dossiers_a_fermer/_body_dossier_ferme_partenaire_tous.html.twig');
    }
}
