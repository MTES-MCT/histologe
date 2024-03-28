<?php

namespace App\Factory\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Model\InformationProcedure;
use App\Utils\DataPropertyArrayFilter;
use Symfony\Component\Serializer\SerializerInterface;

class InformationProcedureFactory
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function createFromSignalementDraftPayload(array $payload): InformationProcedure
    {
        $data = DataPropertyArrayFilter::filterByPrefix(
            $payload,
            SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_PROCEDURE
        );

        return $this->serializer->deserialize(json_encode($data), InformationProcedure::class, 'json');
    }

    public static function createFromArray(array $data): InformationProcedure
    {
        return new InformationProcedure(
            infoProcedureBailleurPrevenu: $data['info_procedure_bailleur_prevenu'] ?? null,
            infoProcedureAssuranceContactee: $data['info_procedure_assurance_contactee'] ?? null,
            infoProcedureDepartApresTravaux: $data['info_procedure_depart_apres_travaux'] ?? null,
            infoProcedureReponseAssurance: $data['info_procedure_reponse_assurance'] ?? null,
            utilisationServiceOkPrevenirBailleur: $data['utilisation_service_ok_prevenir_bailleur'] ?? null,
            utilisationServiceOkVisite: $data['utilisation_service_ok_visite'] ?? null,
            utilisationServiceOkDemandeLogement: $data['utilisation_service_ok_demande_logement'] ?? null,
            utilisationServiceOkCgu: $data['utilisation_service_cgu'] ?? null
        );
    }
}
