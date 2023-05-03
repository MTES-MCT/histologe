<?php

namespace App\Factory;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;

class PartnerFactory
{
    public function __construct()
    {
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
            ->setIsArchive(false)
            ->setCreatedAt(new \DateTimeImmutable());

        if (!empty($insee)) {
            $partner->setInsee(array_map('trim', explode(',', $insee)));
        }

        return $partner;
    }

    // build default competences according to partner type
    public function buildCompetences(PartnerType $type): ?array
    {
        $competences = [];
        switch ($type) {
            case PartnerType::ADIL:
                $competences[] = Qualification::ACCOMPAGNEMENT_JURIDIQUE;
                $competences[] = Qualification::CONCILIATION;
                $competences[] = Qualification::NON_DECENCE_ENERGETIQUE;

                break;
            case PartnerType::ARS:
                $competences[] = Qualification::ARRETES;
                $competences[] = Qualification::DIOGENE;
                $competences[] = Qualification::INSALUBRITE;
                $competences[] = Qualification::VISITES;
                break;
            case PartnerType::BAILLEUR_SOCIAL:
                $competences[] = Qualification::DIOGENE;
                $competences[] = Qualification::HEBERGEMENT_RELOGEMENT;
                $competences[] = Qualification::MISE_EN_SECURITE_PERIL;
                $competences[] = Qualification::NON_DECENCE;
                $competences[] = Qualification::NUISIBLES;
                $competences[] = Qualification::RSD;
                $competences[] = Qualification::NON_DECENCE_ENERGETIQUE;
                break;
            case PartnerType::CAF_MSA:
                $competences[] = Qualification::CONSIGNATION_AL;
                $competences[] = Qualification::NON_DECENCE;
                $competences[] = Qualification::VISITES;
                $competences[] = Qualification::NON_DECENCE_ENERGETIQUE;
                break;
            case PartnerType::CCAS:
                $competences[] = Qualification::ACCOMPAGNEMENT_SOCIAL;
                $competences[] = Qualification::CONCILIATION;
                $competences[] = Qualification::DIOGENE;
                break;
            case PartnerType::COMMUNE_SCHS:
                $competences[] = Qualification::ARRETES;
                $competences[] = Qualification::CONCILIATION;
                $competences[] = Qualification::DIOGENE;
                $competences[] = Qualification::INSALUBRITE;
                $competences[] = Qualification::MISE_EN_SECURITE_PERIL;
                $competences[] = Qualification::NUISIBLES;
                $competences[] = Qualification::RSD;
                $competences[] = Qualification::VISITES;
                $competences[] = Qualification::NON_DECENCE_ENERGETIQUE;
                break;
            case PartnerType::CONCILIATEURS:
                $competences[] = Qualification::CONCILIATION;
                break;
            case PartnerType::CONSEIL_DEPARTEMENTAL:
                $competences[] = Qualification::ACCOMPAGNEMENT_SOCIAL;
                $competences[] = Qualification::ACCOMPAGNEMENT_TRAVAUX;
                $competences[] = Qualification::FSL;
                break;
            case PartnerType::DDETS:
                $competences[] = Qualification::DALO;
                $competences[] = Qualification::HEBERGEMENT_RELOGEMENT;
                break;
            case PartnerType::DDT_M:
                $competences[] = Qualification::ARRETES;
                $competences[] = Qualification::CONCILIATION;
                $competences[] = Qualification::DALO;
                $competences[] = Qualification::HEBERGEMENT_RELOGEMENT;
                break;
            case PartnerType::DISPOSITIF_RENOVATION_HABITAT:
                $competences[] = Qualification::ACCOMPAGNEMENT_TRAVAUX;
                $competences[] = Qualification::CONCILIATION;
                $competences[] = Qualification::VISITES;
                $competences[] = Qualification::NON_DECENCE_ENERGETIQUE;
                break;
            case PartnerType::EPCI:
                $competences[] = Qualification::CONCILIATION;
                break;
            case PartnerType::OPERATEUR_VISITES_ET_TRAVAUX:
                $competences[] = Qualification::ACCOMPAGNEMENT_TRAVAUX;
                $competences[] = Qualification::CONCILIATION;
                $competences[] = Qualification::NON_DECENCE_ENERGETIQUE;
                break;
            case PartnerType::PREFECTURE:
                $competences[] = Qualification::DALO;
                break;
        }

        return $competences;
    }
}
