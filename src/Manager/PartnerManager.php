<?php

namespace App\Manager;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Factory\PartnerFactory;
use Doctrine\Persistence\ManagerRegistry;

class PartnerManager extends AbstractManager
{
    public function __construct(
        private PartnerFactory $partnerFactory,
        protected ManagerRegistry $managerRegistry,
        protected string $entityName = Partner::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrGet(Territory $territory,
                                string $name = null,
                                string $email = null,
                                bool $isCommune = false,
                                string $insee = null): Partner
    {
        $partner = $this->findOneBy(['nom' => $name, 'territory' => $territory]);
        if (null === $partner) {
            $partner = $this->partnerFactory->createInstanceFrom($territory, $name, $email, $isCommune, $insee);
            $this->save($partner);

            return $partner;
        }

        return $partner;
    }
}
