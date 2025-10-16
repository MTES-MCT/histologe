<?php

namespace App\Service;

use App\Dto\ReponseInjonctionBailleur;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;

class ReponseInjectionBailleurManager
{
    public function __construct(
        private readonly SuiviManager $suiviManager,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly AutoAssigner $autoAssigner,

        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handleResponse(ReponseInjonctionBailleur $reponseInjonctionBailleur): void
    {
        $signalement = $reponseInjonctionBailleur->getSignalement();
        $reponse = $reponseInjonctionBailleur->getReponse();
        $description = $reponseInjonctionBailleur->getDescription();
        switch ($reponse) {
            case ReponseInjonctionBailleur::REPONSE_OUI:
                $contenu = 'Le bailleur s\'engage à résoudre les désordres signalés.';
                $category = SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI;
                $this->suiviManager->createSuivi(signalement: $signalement, description: $contenu, type: Suivi::TYPE_AUTO, category: $category, isPublic: true);
                break;
            case ReponseInjonctionBailleur::REPONSE_OUI_AVEC_AIDE:
                $contenu = 'Le bailleur s\'engage à résoudre les désordres signalés.';
                $category = SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE;
                $this->suiviManager->createSuivi(signalement: $signalement, description: $contenu, type: Suivi::TYPE_AUTO, category: $category, isPublic: true);
                $this->createInjonctionBailleurCommentaireSuivi($signalement, $description);
                break;
            case ReponseInjonctionBailleur::REPONSE_NON:
                $contenu = 'Le bailleur refuse de résoudre les désordres signalés, le signalement va être pris en charge par les partenaires compétents.';
                $category = SuiviCategory::INJONCTION_BAILLEUR_REPONSE_NON;
                $this->suiviManager->createSuivi(signalement: $signalement, description: $contenu, type: Suivi::TYPE_AUTO, category: $category, isPublic: true);
                $this->createInjonctionBailleurCommentaireSuivi($signalement, $description);
                $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
                $this->entityManager->flush();
                $hasAssignablePartners = $this->autoAssigner->assign($signalement, true);
                if (count($hasAssignablePartners)) {
                    $this->autoAssigner->assign($signalement);
                } else {
                    $this->notificationAndMailSender->sendNewSignalement($signalement);
                }
                break;
        }
    }

    private function createInjonctionBailleurCommentaireSuivi(Signalement $signalement, string $description): void
    {
        $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::INJONCTION_BAILLEUR_REPONSE_COMMENTAIRE,
        );
    }
}
