<?php

namespace App\Controller\Back;

use App\Entity\JobEvent;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/idoss')]
class IdossController extends AbstractController
{
    #[Route('/log', name: 'back_idoss_log')]
    #[IsGranted('ROLE_ADMIN')]
    public function log(SignalementRepository $signalementRepository): Response
    {
        $errors = $signalementRepository->findSynchroIdoss(JobEvent::STATUS_FAILED);
        $success = $signalementRepository->findSynchroIdoss(JobEvent::STATUS_SUCCESS);

        return $this->render('back/idoss/log.html.twig', [
            'errors' => $errors,
            'success' => $success,
        ]);
    }
}
