<?php

namespace App\Service;

use App\Dto\ReponseInjonctionBailleur;
use App\Dto\StopProcedure;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Repository\UserRepository;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class InjonctionBailleurService
{
    public const string DELAIS_DE_REPONSE = '3 weeks';

    public function __construct(
        private readonly SuiviManager $suiviManager,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly AutoAssigner $autoAssigner,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly Security $security,
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
                $this->notificationAndMailSender->sendNewSignalement($signalement);
                $this->autoAssigner->assign($signalement);
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

    public function handleStopProcedure(StopProcedure $stopProcedure): void
    {
        $signalement = $stopProcedure->getSignalement();
        $description = $stopProcedure->getDescription();

        $adminUser = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
        // TODO : est-ce que le bailleur est vraiment un user ?
        /** @var User $user */
        $user = $this->security->getUser();

        $contenu = 'Le bailleur souhaite arrêter la procédure d\'injonction, le signalement va être pris en charge par les partenaires compétents.';
        $category = SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR;
        $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $contenu,
            type: Suivi::TYPE_AUTO,
            category: $category,
            user: $adminUser,
            isPublic: true
        );

        $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_AUTO,
            user: $user,
            category: SuiviCategory::INJONCTION_BAILLEUR_COMMENTAIRE_ARRET,
        );

        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->entityManager->flush();
        $this->notificationAndMailSender->sendNewSignalement($signalement);
        $this->autoAssigner->assign($signalement);
    }
}
