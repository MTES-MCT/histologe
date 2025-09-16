<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Messenger\Message\ListExportMessage;
use App\Service\Signalement\Export\SignalementExportFiltersDisplay;
use App\Service\Signalement\Export\SignalementExportSelectableColumns;
use App\Service\Signalement\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/export/signalement')]
class ExportSignalementController extends AbstractController
{
    #[Route('/', name: 'back_signalement_list_export', methods: ['GET'])]
    public function index(
        Request $request,
        SignalementExportFiltersDisplay $signalementExportFiltersDisplay,
        SignalementManager $signalementManager,
        SearchFilter $searchFilter,
        DenormalizerInterface $denormalizer,
        ValidatorInterface $validator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $signalementSearchQuery = $this->createSignalementSearchQueryFromCookie($request, $denormalizer, $validator);
        $filters = null !== $signalementSearchQuery
            ? $searchFilter->setRequest($signalementSearchQuery)->buildFilters($user)
            : ['isImported' => 'oui'];

        $count_signalements = $signalementManager->findSignalementAffectationList($user, $filters, true);
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
        SearchFilter $searchFilter,
        DenormalizerInterface $denormalizer,
        ValidatorInterface $validator,
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

        $signalementSearchQuery = $this->createSignalementSearchQueryFromCookie($request, $denormalizer, $validator);
        $filters = null !== $signalementSearchQuery
            ? $searchFilter->setRequest($signalementSearchQuery)->buildFilters($user)
            : ['isImported' => 'oui'];

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

    private function createSignalementSearchQueryFromCookie(
        Request $request,
        DenormalizerInterface $denormalizer,
        ValidatorInterface $validator,
    ): ?SignalementSearchQuery {
        $cookieValue = $request->cookies->get('list-signalements-filters');

        if (null === $cookieValue) {
            return null;
        }

        try {
            parse_str($cookieValue, $filteredData);

            // Conversion des types pour éviter les erreurs de désérialisation
            if (isset($filteredData['page'])) {
                $filteredData['page'] = (int) $filteredData['page'];
            }
            if (isset($filteredData['criticiteScoreMin'])) {
                $filteredData['criticiteScoreMin'] = (float) $filteredData['criticiteScoreMin'];
            }
            if (isset($filteredData['criticiteScoreMax'])) {
                $filteredData['criticiteScoreMax'] = (float) $filteredData['criticiteScoreMax'];
            }
            if (isset($filteredData['sansSuiviPeriode'])) {
                $filteredData['sansSuiviPeriode'] = (int) $filteredData['sansSuiviPeriode'];
            }
            if (isset($filteredData['usagerAbandonProcedure'])) {
                $filteredData['usagerAbandonProcedure'] = filter_var($filteredData['usagerAbandonProcedure'], \FILTER_VALIDATE_BOOLEAN);
            }

            $signalementSearchQuery = $denormalizer->denormalize(
                $filteredData,
                SignalementSearchQuery::class,
                null,
                ['allow_extra_attributes' => false]
            );

            $violations = $validator->validate($signalementSearchQuery);

            if (count($violations) > 0) {
                return null;
            }

            return $signalementSearchQuery;
        } catch (\Exception $exception) {
            return null;
        }
    }
}
