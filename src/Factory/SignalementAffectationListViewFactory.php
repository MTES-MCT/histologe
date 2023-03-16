<?php

namespace App\Factory;

use App\Dto\SignalementAffectationListView;
use App\Entity\Enum\AffectationStatus;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementAffectationListViewFactory
{
    public function createInstanceFrom(UserInterface|User $user, array $data): SignalementAffectationListView
    {
        $affectations = $this->parseAffectations($data['rawAffectations']);
        if ($user->isUserPartner() || $user->isPartnerAdmin()) {
            $statusAffectation = $affectations[$user->getPartner()->getNom()]['statut'];
            $status = AffectationStatus::tryFrom($statusAffectation)?->mapSignalementStatus();
        } else {
            $status = $data['statut'];
        }

        return new SignalementAffectationListView(
            id: $data['id'],
            uuid: $data['uuid'],
            reference: $data['reference'],
            createdAt: $data['createdAt'],
            statut: $status,
            scoreCreation: $data['scoreCreation'],
            newScoreCreation: $data['newScoreCreation'],
            isNotOccupant: $data['isNotOccupant'],
            nomOccupant: $data['nomOccupant'],
            prenomOccupant: $data['prenomOccupant'],
            adresseOccupant: $data['adresseOccupant'],
            villeOccupant: $data['villeOccupant'],
            lastSuiviAt: $data['lastSuiviAt'],
            lastSuiviBy: $data['lastSuiviBy'],
            affectations: $affectations
        );
    }

    private function parseAffectations(?string $rawAffectations): array
    {
        if (null === $rawAffectations) {
            return [];
        }

        $affectations = [];
        $affectationsList = explode(SignalementAffectationListView::SEPARATOR_GROUP_CONCAT, $rawAffectations);
        foreach ($affectationsList as $affectationItem) {
            list($partner, $status) = explode(SignalementAffectationListView::SEPARATOR_CONCAT, $affectationItem);
            $statusAffectation = AffectationStatus::from($status)->value;
            $affectations[$partner] = [
                'partner' => $partner,
                'statut' => $statusAffectation,
            ];
        }

        return $affectations;
    }
}
