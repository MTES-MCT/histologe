<?php

namespace App\Service;

use App\Dto\ReponseInjonctionBailleur;
use App\Dto\StopProcedure;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;

class InjonctionBailleurService
{
    public const string DELAIS_DE_REPONSE = '3 weeks';

    public function __construct(
        private readonly SuiviManager $suiviManager,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly AutoAssigner $autoAssigner,
        private readonly EntityManagerInterface $entityManager,
        private readonly AffectationManager $affectationManager,
        private readonly UserManager $userManager,
        private readonly SignalementManager $signalementManager,
        private readonly PartnerRepository $partnerRepository,
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
                $this->assignHelpingPartners($signalement);
                break;
            case ReponseInjonctionBailleur::REPONSE_NON:
                $contenu = 'Le bailleur refuse de résoudre les désordres signalés, le signalement va être pris en charge par les partenaires compétents.';
                $category = SuiviCategory::INJONCTION_BAILLEUR_REPONSE_NON;
                $this->suiviManager->createSuivi(signalement: $signalement, description: $contenu, type: Suivi::TYPE_AUTO, category: $category, isPublic: true);
                $this->createInjonctionBailleurCommentaireSuivi($signalement, $description);
                $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
                $this->entityManager->flush();
                $this->notificationAndMailSender->sendNewSignalement($signalement);
                $this->autoAssigner->assign($signalement);
                break;
        }
    }

    private function createInjonctionBailleurCommentaireSuivi(Signalement $signalement, string $description): void
    {
        $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: HtmlCleaner::cleanFrontEndEntry($description),
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::INJONCTION_BAILLEUR_REPONSE_COMMENTAIRE,
        );
    }

    public function assignHelpingPartners(Signalement $signalement): void
    {
        $affectablePartners = $this->signalementManager->findAffectablePartners($signalement, filterInjonctionBailleur: true);
        // Pour l'instant, on ne filtre que les partenaires ayant la compétence AIDE_BAILLEURS
        // Peut-être qu'il faudra être plus proche de l'auto-affectation ? (insee occupant, procédures suspectées, etc.)
        $helpingPartnersFromTerritory = $affectablePartners['not_affected'];

        $adminUser = $this->userManager->getSystemUser();

        foreach ($helpingPartnersFromTerritory as $partnerItem) {
            $partner = $this->partnerRepository->find($partnerItem['id']);
            $affectation = $this->affectationManager->createAffectationFrom(
                $signalement,
                $partner,
                $adminUser
            );
            $signalement->addAffectation($affectation);
        }
        $this->affectationManager->flush();
    }

    public function handleStopProcedure(StopProcedure $stopProcedure): void
    {
        $signalement = $stopProcedure->getSignalement();
        $description = $stopProcedure->getDescription();

        $contenu = 'Le bailleur souhaite arrêter la procédure d\'injonction, le signalement va être pris en charge par les partenaires compétents.';
        $category = SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR;
        $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $contenu,
            type: Suivi::TYPE_AUTO,
            category: $category,
            isPublic: true
        );

        $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: HtmlCleaner::cleanFrontEndEntry($description),
            type: Suivi::TYPE_AUTO,
            category: SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR_COMMENTAIRE,
        );

        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->entityManager->flush();
        $this->notificationAndMailSender->sendNewSignalement($signalement);
        $this->autoAssigner->assign($signalement);
    }
}
