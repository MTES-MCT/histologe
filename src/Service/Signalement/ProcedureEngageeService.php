<?php

namespace App\Service\Signalement;

use App\Entity\Enum\ProcedureCategory;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\SignalementProcedure;
use App\Entity\User;
use App\Manager\SuiviManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

class ProcedureEngageeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SuiviManager $suiviManager,
        private readonly Security $security,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param FormInterface<mixed> $form
     */
    public function updateProcedureEngageeForSignalement(Signalement $signalement, FormInterface $form, bool &$isEdition = false): void
    {
        $lastSuivi = $signalement->getSuivisWithCategory(SuiviCategory::ADD_OR_EDIT_PROCEDURE_ENGAGEE)->last();
        $ancienCommentaire = $lastSuivi ? ($lastSuivi->getOriginalData()['commentaire'] ?? null) : null;
        $nouveauCommentaire = $form->get('commentaire')->getData();
        $anciennesProcedures = array_map(
            static fn (SignalementProcedure $procedure): string => $procedure->getProcedureType()->value,
            $signalement->getSignalementProcedures(ProcedureCategory::PROCEDURE_ENGAGEE)->toArray(),
        );
        $nouvellesProcedures = array_map(
            static fn (ProcedureType $procedure): string => $procedure->value,
            $form->get('procedureEngagees')->getData(),
        );
        sort($anciennesProcedures);
        sort($nouvellesProcedures);

        if ($anciennesProcedures === $nouvellesProcedures && trim((string) $ancienCommentaire) === trim((string) $nouveauCommentaire)) {
            // aucun changement détecté, on sort
            return;
        }
        if (count($anciennesProcedures)) {
            $isEdition = true;
        }

        foreach ($signalement->getSignalementProcedures(ProcedureCategory::PROCEDURE_ENGAGEE) as $signalementProcedure) {
            $this->entityManager->remove($signalementProcedure);
        }
        $this->entityManager->flush();
        foreach ($form->get('procedureEngagees')->getData() as $procedure) {
            $signalementProcedure = (new SignalementProcedure())->setSignalement($signalement)->setProcedureType($procedure)->setProcedureCategory(ProcedureCategory::PROCEDURE_ENGAGEE);
            $signalement->addSignalementProcedure($signalementProcedure);
            $this->entityManager->persist($signalementProcedure);
        }

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory());
        $suivi = $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $this->twig->render('suivi/back_signalement_edit_procedure_engagees.html.twig', [
                'isEdition' => $isEdition,
                'user' => $user,
                'partner' => $partner,
                'signalement' => $signalement,
                'commentaire' => $nouveauCommentaire,
            ]),
            category: SuiviCategory::ADD_OR_EDIT_PROCEDURE_ENGAGEE,
            partner: $partner,
            user: $user,
        );
        $suivi->setOriginalData(['commentaire' => $nouveauCommentaire]);
    }
}
