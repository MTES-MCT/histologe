<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Repository\SignalementRepository;
use App\Service\Idoss\IdossService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class iDossController extends AbstractController
{
    #[Route('/idoss', name: 'iDoss')]
    public function index(IdossService $idossService, SignalementRepository $signalementRepository): Response
    {
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000014']);
        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()[0];
        $dossierMessage = new DossierMessage($affectation);

        if (!$signalement->getSynchroData('idoss')) {
            $jobEvent = $idossService->pushDossier($dossierMessage); // 72375

            return new Response($jobEvent->getResponse());
        }

        $jobEvent = $idossService->uploadFiles($affectation->getPartner(), $signalement);

        return new Response($jobEvent->getStatus());
    }
}
