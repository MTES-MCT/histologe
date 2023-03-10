<?php

namespace App\Factory;

use App\Dto\SignalementAffectationListView;

class SignalementAffectationListViewFactory
{
    public function createInstanceFrom(array $data): SignalementAffectationListView
    {
        return new SignalementAffectationListView(
            id: $data['id'],
            uuid: $data['uuid'],
            reference: $data['reference'],
            statut: $data['statut'],
            scoreCreation: $data['scoreCreation'],
            newScoreCreation: $data['newScoreCreation'],
            isNotOccupant: $data['isNotOccupant'],
            nomOccupant: $data['nomOccupant'],
            prenomOccupant: $data['prenomOccupant'],
            adresseOccupant: $data['adresseOccupant'],
            villeOccupant: $data['villeOccupant'],
            lastSuiviAt: $data['lastSuiviAt'],
            affectations: $this->buildAffectations($data['rawAffectations'])
        );
    }

    private function buildAffectations(?string $rawAffectations): array
    {
        if (null === $rawAffectations) {
            return [];
        }

        $affectations = [];
        $affectationsList = explode('--', $rawAffectations);
        foreach ($affectationsList as $affectationItem) {
            list($partner, $status) = explode('||', $affectationItem);
            $affectations[] = [
                'partner' => $partner,
                'statut' => $status,
            ];
        }

        return $affectations;
    }
}
