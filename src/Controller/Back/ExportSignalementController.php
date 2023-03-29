<?php

namespace App\Controller\Back;

use App\Manager\SignalementManager;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class ExportSignalementController extends AbstractController
{
    #[Route('/export/signalement', name: 'back_signalement_list_export')]
    public function exportCsv(
        Request $request,
        SignalementManager $signalementManager,
    ): StreamedResponse {
        $filters = $request->getSession()->get('filters');
        /** @var User $user */
        $user = $this->getUser();
        $signalementAffectationList = $signalementManager->findSignalementAffectationIterable($user, $filters);
        $response = new StreamedResponse();
        $response->setCallback(function () use ($signalementAffectationList) {
            $handle = fopen('php://output', 'w');
            foreach ($signalementAffectationList as $signalementAffectationItem) {
                fputcsv($handle, get_object_vars($signalementAffectationItem));
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="export-histologe-'.date('dmY').'.csv"'
        );

        return $response;
    }
}
