<?php

namespace App\Factory;

use App\Dto\SignalementAffectationListView;
use App\Entity\User;
use App\Service\Signalement\SignalementAffectationHelper;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementAffectationListViewFactory
{
    public function createInstanceFrom(UserInterface|User $user, array $data): SignalementAffectationListView
    {
        list($status, $affectations) = SignalementAffectationHelper::getStatusAndAffectationFrom($user, $data);

        return new SignalementAffectationListView(
            id: $data['id'],
            uuid: $data['uuid'],
            reference: $data['reference'],
            createdAt: $data['createdAt'],
            statut: $status,
            score: $data['score'],
            isNotOccupant: $data['isNotOccupant'],
            nomOccupant: $data['nomOccupant'],
            prenomOccupant: $data['prenomOccupant'],
            adresseOccupant: $data['adresseOccupant'],
            villeOccupant: $data['villeOccupant'],
            lastSuiviAt: $data['lastSuiviAt'],
            lastSuiviBy: $data['lastSuiviBy'],
            affectations: $affectations,
            qualifications: SignalementAffectationHelper::getQualificationFrom($data)
        );
    }
}
