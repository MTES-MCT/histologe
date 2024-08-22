<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Service\Signalement\Export\SignalementExportLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/export/signalement')]
class ExportSignalementController extends AbstractController
{
    #[Route('/', name: 'back_signalement_list_export', methods: ['GET'])]
    public function index(
        Request $request,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $filters = $request->getSession()->get('filters');

        return $this->render('back/signalement_export/index.html.twig', [
            'filters' => $filters
        ]);
    }

    #[Route('/', name: 'back_signalement_list_export')]
    public function exportCsv(
        Request $request,
        SignalementExportLoader $signalementExportLoader
    ): RedirectResponse|StreamedResponse {
        /** @var User $user */
        $user = $this->getUser();
        $filters = $request->getSession()->get('filters');
        try {
            $response = new StreamedResponse();
            $response->setCallback(function () use ($signalementExportLoader, $filters, $user) {
                $signalementExportLoader->load($user, $filters);
            });

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                'export-histologe-'.date('dmY').'.csv'
            );
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        } catch (\ErrorException $e) {
            $this->addFlash('error', 'Problème d\'identification de votre demande. Merci de réessayer.');
            throw new \Exception('Erreur lors de l\'export du fichier par l\'user "'.$user->getId().'" : '.$e->getMessage().' - '.print_r($filters, true));
        }
    }
}
