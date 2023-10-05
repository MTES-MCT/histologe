<?php

namespace App\Factory\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Model\SituationFoyer;
use App\Utils\DataPropertyArrayFilter;
use Symfony\Component\Serializer\SerializerInterface;

class SituationFoyerFactory
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function createFromSignalementDraftPayload(array $payload): SituationFoyer
    {
        $data = DataPropertyArrayFilter::filterByPrefix(
            $payload,
            SignalementDraftRequest::PREFIX_PROPERTIES_SITUATION_FOYER
        );

        return $this->serializer->deserialize(json_encode($data), SituationFoyer::class, 'json');
    }

    public static function createFromArray(array $data): SituationFoyer
    {
        return new SituationFoyer(
            logementSocialDemandeRelogement: $data['logement_social_demande_relogement'] ?? null,
            logementSocialAllocation: $data['logement_social_allocation'] ?? null,
            logementSocialAllocationCaisse: $data['logement_social_allocation_caisse'] ?? null,
            logementSocialDateNaissance: $data['logement_social_date_naissance'] ?? null,
            logementSocialMontantAllocation: $data['logement_social_montant_allocation'] ?? null,
            logementSocialNumeroAllocataire: $data['logement_social_numero_allocataire'] ?? null,
            travailleurSocialQuitteLogement: $data['travailleur_social_quitte_logement'] ?? null,
            travailleurSocialAccompagnement: $data['travailleur_social_accompagnement'] ?? null,
            travailleurSocialAccompagnementDeclarant: $data['travailleur_social_accompagnement_declarant'] ?? null
        );
    }
}
