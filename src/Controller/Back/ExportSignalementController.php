<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Manager\SignalementManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
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
    ) {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('export_token_'.$user->getId(), $request->get('_token'))) {
            $filters = $request->getSession()->get('filters');
            $response = new StreamedResponse();
            $response->setCallback(function () use ($signalementManager, $filters, $user) {
                $handle = fopen('php://output', 'w');
                foreach ($signalementManager->findSignalementAffectationIterable($user, $filters) as $key => $signalementAffectationItem) {
                    if (0 === $key) {
                        fputcsv($handle, array_keys(get_object_vars($signalementAffectationItem)), ';');
                    }
                    fputcsv($handle, get_object_vars($signalementAffectationItem), ';');
                }
                fclose($handle);
            });

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                'export-histologe-'.date('dmY').'.csv'
            );
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', $disposition);

            $response->send();
        } else {
            return $this->redirectToRoute('back_index');
        }
    }
}
