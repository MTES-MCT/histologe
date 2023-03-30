<?php

namespace App\Controller\Back;

use App\Manager\SignalementManager;
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
        $signalementAffectationIterable = $signalementManager->findSignalementAffectationIterable($this->getUser(), $filters);
        $response = new StreamedResponse();
        $response->setCallback(function () use ($signalementAffectationIterable) {
            $handle = fopen('php://output', 'w');
            foreach ($signalementAffectationIterable as $key => $signalementAffectationItem) {
                if (0 === $key) {
                    fputcsv($handle, array_keys(get_object_vars($signalementAffectationItem)), ';');
                }
                fputcsv($handle, get_object_vars($signalementAffectationItem), ';');
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
