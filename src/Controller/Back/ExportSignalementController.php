<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Manager\SignalementManager;
use App\Messenger\Message\ListExportMessage;
use App\Service\Signalement\Export\SignalementExportFiltersDisplay;
use App\Service\Signalement\Export\SignalementExportSelectableColumns;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/export/signalement')]
class ExportSignalementController extends AbstractController
{
    #[Route('/', name: 'back_signalement_list_export', methods: ['GET'])]
    public function index(
        Request $request,
        SignalementExportFiltersDisplay $signalementExportFiltersDisplay,
        SignalementManager $signalementManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $filters = $options = $request->getSession()->get('filters', ['isImported' => '1']);
        $count_signalements = $signalementManager->findSignalementAffectationList($user, $options, true);
        $textFilters = $signalementExportFiltersDisplay->filtersToText($filters);

        return $this->render('back/signalement_export/index.html.twig', [
            'filters' => $textFilters,
            'selectable_cols' => SignalementExportSelectableColumns::getColumns(),
            'selected_cols' => $request->getSession()->get('selectedCols'),
            'count_signalements' => $count_signalements,
        ]);
    }

    #[Route('/file', name: 'back_signalement_list_export_file', methods: ['POST'])]
    public function exportFile(
        Request $request,
        MessageBusInterface $messageBus,
    ): RedirectResponse {
        $selectedColumns = $request->get('cols') ?? [];
        $format = $request->get('file-format');

        if (!in_array($format, ['csv', 'xlsx'])) {
            $request->getSession()->set('selectedCols', $selectedColumns);
            $this->addFlash('error', "Merci de sélectionner le format de l'export.");

            return $this->redirectToRoute('back_signalement_list_export');
        }

        /** @var User $user */
        $user = $this->getUser();
        $filters = $request->getSession()->get('filters') ?? [];

        $message = (new ListExportMessage())
            ->setUserId($user->getId())
            ->setFormat($format)
            ->setFilters($filters)
            ->setSelectedColumns($selectedColumns);

        $messageBus->dispatch($message);

        $this->addFlash(
            'success',
            \sprintf(
                'L\'export vous sera envoyé par e-mail à l\'adresse suivante : %s. Il arrivera d\'ici quelques minutes. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                $user->getEmail()
            )
        );

        return $this->redirectToRoute('back_signalement_list_export');
    }
}
