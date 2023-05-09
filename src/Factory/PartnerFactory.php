<?php

namespace App\Factory;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Territory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PartnerFactory
{
    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function createInstanceFrom(
        Territory $territory,
        string $name = null,
        string $email = null,
        PartnerType $type = null,
        string $insee = null
    ): Partner {
        $partner = (new Partner())
            ->setTerritory($territory)
            ->setNom($name)
            ->setEmail($email)
            ->setType($type)
            ->setCompetence($this->buildCompetences($type))
            ->setIsArchive(false);

        if (!empty($insee)) {
            $partner->setInsee(array_map('trim', explode(',', $insee)));
        }

        return $partner;
    }

    // build default competences according to partner type
    public function buildCompetences(PartnerType $type): ?array
    {
        $types = $this->parameterBag->get('competence_per_type');
        $competences = [];
        if (\array_key_exists($type->name, $types)) {
            foreach ($types[$type->name] as $competence) {
                $competences[] = $competence;
            }
        }

        return $competences;
    }
}
