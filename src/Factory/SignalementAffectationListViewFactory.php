<?php

namespace App\Factory;

use App\Dto\SignalementAffectationListView;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\User;
use App\Service\Signalement\SignalementAffectationHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SignalementAffectationListViewFactory
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        private Security $security,
        ) {
    }

    public function createInstanceFrom(UserInterface|User $user, array $data): SignalementAffectationListView
    {
        $signalement = SignalementAffectationHelper::getSignalementFromDataForVoter($data);
        $canDeleteSignalement = $this->security->isGranted('SIGN_DELETE', $signalement);

        list($status, $affectations) = SignalementAffectationHelper::getStatusAndAffectationFrom($user, $data);

        /** @var ?ProfileDeclarant $profileDeclarant */
        $profileDeclarant = $data['profileDeclarant'];
        $signalementAffectationListView = new SignalementAffectationListView(
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
            codepostalOccupant: $data['cpOccupant'],
            villeOccupant: $data['villeOccupant'],
            lastSuiviAt: $data['lastSuiviAt'],
            lastSuiviBy: $data['lastSuiviBy'],
            lastSuiviIsPublic: $data['lastSuiviIsPublic'],
            profileDeclarant: $profileDeclarant?->label(),
            affectations: $affectations,
            qualifications: SignalementAffectationHelper::getQualificationFrom($data),
            qualificationsStatuses: SignalementAffectationHelper::getQualificationStatusesFrom($data),
            conclusionsProcedure: SignalementAffectationHelper::parseConclusionProcedure($data['conclusionsProcedure']),
            canDeleteSignalement: $canDeleteSignalement,
            csrfToken: $canDeleteSignalement ? $this->csrfTokenManager->getToken('signalement_delete_'.$data['id']) : null,
        );

        return $signalementAffectationListView;
    }
}
