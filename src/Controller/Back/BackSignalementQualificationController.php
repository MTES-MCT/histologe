<?php

namespace App\Controller\Back;

use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Service\Signalement\SignalementQualificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class BackSignalementQualificationController extends AbstractController
{
    #[Route('/{uuid}/qualification/{signalement_qualification}/editer', name: 'back_signalement_qualification_editer')]
    public function editQualification(
        Request $request,
        Signalement $signalement,
        SignalementQualification $signalementQualification,
        EntityManagerInterface $entityManager
    ): RedirectResponse|JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_EDIT_NDE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_nde_'.$signalement->getId(), $request->get('_token'))) {
            $dataDateEntree = $request->get('signalement-edit-nde-date-entree');
            $dataSuperficie = $request->get('signalement-edit-nde-superficie');

            $dataDernierBail = $request->get('signalement-edit-nde-dernier-bail');
            $dataConsoEnergie = $request->get('signalement-edit-nde-conso-energie');
            $dataDpe = 'null' === $request->get('signalement-edit-nde-dpe') ? null : (int) $request->get('signalement-edit-nde-dpe');
            $dataDpeDate = $request->get('signalement-edit-nde-dpe-date');

            if ('after' === $dataDateEntree && $signalement->getDateEntree()->format('Y') < '2023 ') {
                // TODO :  voir avec Emilien les dates qu'on met en fonction des différents cas : ici date du jour ?
                // $signalement->setDateEntree(new DateTimeImmutable($dataDateEntree));
            }

            if ('before' === $dataDateEntree && $signalement->getDateEntree()->format('Y') >= '2023 ') {
                // TODO :  voir avec Emilien les dates qu'on met en fonction des différents cas : ici 01/01/1970
                // $signalement->setDateEntree(new DateTimeImmutable($dataDateEntree));
            }

            if (null !== $dataSuperficie && $signalement->getSuperficie() !== $dataSuperficie) {
                $signalement->setSuperficie($dataSuperficie);
            }

            if (null !== $dataDernierBail && $signalementQualification->getDernierBailAt()->format('Y-m-d') !== $dataDernierBail) {
                $signalementQualification->setDernierBailAt(new DateTimeImmutable($dataDernierBail));
            }

            $qualificationDetails = $signalementQualification->getDetails();
            if ((null !== $dataConsoEnergie && $qualificationDetails['consommation_energie'] !== $dataConsoEnergie)
            || (null !== $dataDpe && $qualificationDetails['DPE'] !== $dataDpe)
            || (null !== $dataDpeDate && $qualificationDetails['date_dernier_dpe'] !== $dataDpeDate)) {
                $qualificationDetails['consommation_energie'] = $dataConsoEnergie;
                $qualificationDetails['DPE'] = $dataDpe;
                $qualificationDetails['date_dernier_dpe'] = $dataDpeDate;
                $signalementQualification->setDetails($qualificationDetails);
            }

            $qualificationService = new SignalementQualificationService($signalement, $signalementQualification);
            $signalementQualification->setStatus($qualificationService->updateNDEStatus());

            $entityManager->persist($signalement);
            $entityManager->persist($signalementQualification);
            $entityManager->flush();
        } else {
            $this->addFlash('error', "Une erreur est survenu lors de l'édition");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
