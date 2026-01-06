<?php

namespace App\Controller;

use App\Dto\ReponseInjonctionBailleur;
use App\Dto\StopProcedure;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Form\CoordonneesBailleurType;
use App\Form\ReponseInjonctionBailleurType;
use App\Form\StopProcedureType;
use App\Repository\FileRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Security\User\SignalementBailleur;
use App\Service\Files\ImageVariantProvider;
use App\Service\InjonctionBailleur\EngagementTravauxBailleurGenerator;
use App\Service\InjonctionBailleur\InjonctionBailleurService;
use App\Service\Signalement\SignalementDesordresProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier-bailleur')]
class SuiviBailleurController extends AbstractController
{
    #[Route('/', name: 'front_dossier_bailleur', methods: ['GET', 'POST'])]
    public function dossierBailleur(
        Request $request,
        SignalementRepository $signalementRepository,
        SuiviRepository $suiviRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        InjonctionBailleurService $injonctionBailleurService,
        EntityManagerInterface $entityManager,
        FileRepository $fileRepository,
        LoggerInterface $logger,
    ): Response {
        /**
         * @var SignalementBailleur $user
         */
        $user = $this->getUser();
        $signalement = $signalementRepository->findOneBy(['uuid' => $user->getUserIdentifier()]);
        $infoDesordres = $signalementDesordresProcessor->process($signalement);
        $dateLimit = $signalement->getCreatedAt()->modify('+'.InjonctionBailleurService::DELAIS_DE_REPONSE.' -1 day');
        $suiviReponse = $suiviRepository->findOneBy(['signalement' => $signalement, 'category' => SuiviCategory::injonctionBailleurReponseCategories()]);
        $suiviBasculeProcedure = $suiviRepository->findOneBy(['signalement' => $signalement, 'category' => SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR]);

        $engagementTravauxPdf = null;
        $form = null;
        $formStopProcedure = null;
        if (SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
            if ($suiviReponse) {
                if (!$signalement->getMailProprio()) {
                    $form = $this->createForm(CoordonneesBailleurType::class, $signalement, [
                        'action' => $this->generateUrl('front_dossier_bailleur').'#form_coordonnees_bailleur_title',
                    ]);
                    $form->handleRequest($request);
                    if ($form->isSubmitted() && $form->isValid()) {
                        $entityManager->flush();
                        $this->addFlash('success', 'Vos coordonnées ont été enregistrées avec succès.');

                        return $this->redirectToRoute('front_dossier_bailleur');
                    }
                }
                if (in_array($suiviReponse->getCategory(), [SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI, SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE], true)) {
                    $engagementTravauxPdf = $fileRepository->findOneBy(['signalement' => $signalement, 'documentType' => DocumentType::ENGAGEMENT_TRAVAUX_BAILLEUR]);
                    $stopProcedure = new StopProcedure();
                    $stopProcedure->setSignalement($signalement);

                    $formStopProcedure = $this->createForm(StopProcedureType::class, $stopProcedure, [
                        'action' => $this->generateUrl('front_dossier_bailleur').'#form_stop_procedure_bailleur_title',
                    ]);
                    $formStopProcedure->handleRequest($request);

                    if ($formStopProcedure->isSubmitted() && $formStopProcedure->isValid()) {
                        $entityManager->beginTransaction();
                        try {
                            $injonctionBailleurService->handleStopProcedure($stopProcedure);
                            $entityManager->commit();
                            $this->addFlash('success', 'Votre réponse a été enregistrée avec succès.');
                        } catch (\Exception $e) {
                            $logger->critical($e->getMessage());
                            $entityManager->rollback();
                            $this->addFlash('error', 'Une erreur est survenue veuillez réessayer.');
                        }

                        return $this->redirectToRoute('front_dossier_bailleur');
                    }
                }
            } else {
                $reponseInjonctionBailleur = new ReponseInjonctionBailleur();
                $reponseInjonctionBailleur->setSignalement($signalement);
                $form = $this->createForm(ReponseInjonctionBailleurType::class, $reponseInjonctionBailleur, [
                    'action' => $this->generateUrl('front_dossier_bailleur').'#form_reponse_injonction_bailleur_title',
                ]);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager->beginTransaction();
                    try {
                        $injonctionBailleurService->handleResponse($reponseInjonctionBailleur);
                        $entityManager->commit();
                        $this->addFlash('success', 'Votre réponse a été enregistrée avec succès.');
                    } catch (\Exception $e) {
                        $logger->critical($e->getMessage());
                        $entityManager->rollback();
                        $this->addFlash('error', 'Une erreur est survenue veuillez réessayer.');

                        return $this->redirectToRoute('front_dossier_bailleur');
                    }

                    return $this->redirectToRoute('front_dossier_bailleur');
                }
            }
        }

        return $this->render('front/dossier_bailleur.html.twig', [
            'signalement' => $signalement,
            'infoDesordres' => $infoDesordres,
            'suiviReponse' => $suiviReponse,
            'suiviBasculeProcedure' => $suiviBasculeProcedure,
            'dateLimit' => $dateLimit,
            'form' => $form,
            'formStopProcedure' => $formStopProcedure,
            'engagementTravauxPdf' => $engagementTravauxPdf,
        ]);
    }

    #[Route('/engagement-travaux', name: 'front_engagement_travaux_bailleur', methods: ['GET'])]
    public function engagementTravauxBailleur(
        SignalementRepository $signalementRepository,
        FileRepository $fileRepository,
        ImageVariantProvider $imageVariantProvider,
        EngagementTravauxBailleurGenerator $engagementTravauxBailleurGenerator,
    ): Response {
        /**
         * @var SignalementBailleur $user
         */
        $user = $this->getUser();
        $signalement = $signalementRepository->findOneBy(['uuid' => $user->getUserIdentifier()]);
        $pdf = $fileRepository->findOneBy(['signalement' => $signalement, 'documentType' => DocumentType::ENGAGEMENT_TRAVAUX_BAILLEUR]);
        if ($pdf) {
            $file = $imageVariantProvider->getFileVariant($pdf->getFilename());

            return (new BinaryFileResponse($file))->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);
        }

        if (SignalementStatus::INJONCTION_BAILLEUR !== $signalement->getStatut()) {
            throw $this->createNotFoundException();
        }
        $pdfContent = $engagementTravauxBailleurGenerator->generate($signalement);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="engagement-travaux-'.$signalement->getReferenceInjonction().'.pdf"');

        return $response;
    }
}
