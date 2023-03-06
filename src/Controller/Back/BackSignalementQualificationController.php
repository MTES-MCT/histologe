<?php

namespace App\Controller\Back;

use App\Dto\SignalementQualificationNDE;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Manager\SignalementManager;
use App\Service\Signalement\SignalementQualificationService;
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
        EntityManagerInterface $entityManager,
        SignalementManager $signalementManager,
        SignalementQualificationService $signalementQualificationService,
        SignalementQualificationNDE $signalementQualificationNDE
    ): RedirectResponse|JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_EDIT_NDE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_nde_'.$signalement->getId(), $request->get('_token'))) {
            $signalementManager->updateFromSignalementQualification($signalement, $signalementQualification, $signalementQualificationNDE);
        } else {
            $this->addFlash('error', "Une erreur est survenu lors de l'édition");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
