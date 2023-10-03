<?php

namespace App\Factory\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Model\InformationComplementaire;
use App\Utils\DataPropertyArrayFilter;
use Symfony\Component\Serializer\SerializerInterface;

class InformationComplementaireFactory
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function createFromSignalementDraftPayload(array $payload): InformationComplementaire
    {
        $data = DataPropertyArrayFilter::filterByPrefix(
            $payload,
            SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_COMPLEMENTAIRE
        );

        return $this->serializer->deserialize(json_encode($data), InformationComplementaire::class, 'json');
    }

    public static function createFromArray(array $data): InformationComplementaire
    {
        return new InformationComplementaire(
            informationsComplementairesSituationOccupantsBeneficiaireRsa: $data['informations_complementaires_situation_occupants_beneficiaire_rsa'] ?? null,
            informationsComplementairesSituationOccupantsBeneficiaireFsl: $data['informations_complementaires_situation_occupants_beneficiaire_fsl'] ?? null,
            informationsComplementairesSituationOccupantsDateNaissance: $data['informations_complementaires_situation_occupants_date_naissance'] ?? null,
            informationsComplementairesLogementMontantLoyer: $data['informations_complementaires_logement_montant_loyer'] ?? null,
            informationsComplementairesLogementNombreEtages: $data['informations_complementaires_logement_nombre_etages'] ?? null,
            informationsComplementairesLogementAnneeConstruction: $data['informations_complementaires_logement_annee_construction'] ?? null
        );
    }
}
