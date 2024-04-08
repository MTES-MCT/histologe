<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Service\Signalement\Export\SignalementExportLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class ExportSignalementController extends AbstractController
{
    #[Route('/export/signalement', name: 'back_signalement_list_export')]
    public function exportCsv(
        Request $request,
        SignalementExportLoader $signalementExportLoader
    ): RedirectResponse|StreamedResponse {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('export_token_'.$user->getId(), $request->get('_token'))) {
            set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line) {
                throw new \ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            }, \E_WARNING);
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

                return $response->send();
            } catch (\Exception $e) {
                throw new \Exception('Erreur lors de l\'export du fichier par l\'user "'.$user->getId().'" : '.$e->getMessage().' - '.print_r($filters, true));
            }
        }
        $this->addFlash('error', 'Problème d\'identification de votre demande. Merci de réessayer.');

        return $this->redirectToRoute('back_index');
    }
}
