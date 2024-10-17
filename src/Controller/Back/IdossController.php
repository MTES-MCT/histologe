<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Factory\Interconnection\Idoss\DossierMessageFactory;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/idoss')]
#[When('dev')]
#[When('test')]
class IdossController extends AbstractController
{
    #[Route('/log', name: 'back_idoss_log')]
    #[IsGranted('ROLE_ADMIN')]
    public function log(SignalementRepository $signalementRepository): Response
    {
        $errors = $signalementRepository->findSynchroIdossErrors();

        return $this->render('back/idoss/log.html.twig', [
            'errors' => $errors,
        ]);
    }

    #[Route('/retry/{signalement}/{partner}', name: 'back_idoss_retry')]
    #[IsGranted('ROLE_ADMIN')]
    public function retry(
        Signalement $signalement,
        Partner $partner,
        SignalementRepository $signalementRepository,
        AffectationRepository $affectationRepository,
        DossierMessageFactory $dossierMessageFactory,
        MessageBusInterface $bus
    ): Response {
        $errors = $signalementRepository->findSynchroIdossErrors();
        $affectation = $affectationRepository->findOneBy(['signalement' => $signalement, 'partner' => $partner, 'statut' => Affectation::STATUS_ACCEPTED]);
        if (isset($errors[$signalement->getId()]) && $affectation && $dossierMessageFactory->supports($affectation)) {
            $bus->dispatch($dossierMessageFactory->createInstance($affectation));
            $this->addFlash('success', 'La synchronisation a été relancée');
        }

        return $this->redirectToRoute('back_idoss_log');
    }
}
