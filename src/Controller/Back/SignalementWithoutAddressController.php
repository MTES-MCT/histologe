<?php

namespace App\Controller\Back;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\Signalement;
use App\Entity\User;
use App\Exception\Address\TerritoryNotFoundForCityCodeException;
use App\Factory\SuiviDelayedFactory;
use App\Form\SearchSignalementWithoutAddressType;
use App\Repository\SignalementRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Ban\Response\Address as BanAddressResponse;
use App\Service\ListFilters\SearchSignalementWithoutAddress;
use App\Service\MessageHelper;
use App\Service\Signalement\SignalementAddressAnomalyChecker;
use App\Service\Signalement\SignalementAddressUpdater;
use App\Utils\ExportFormat;
use App\Utils\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalements-sans-adresse')]
#[IsGranted('ROLE_ADMIN')]
class SignalementWithoutAddressController extends AbstractController
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementAddressAnomalyChecker $signalementAddressAnomalyChecker,
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
    ) {
    }

    #[Route('/', name: 'back_signalement_without_address_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        [$form, $searchSignalementWithoutAddress, $rows, $total] = $this->handleSearch($request);

        return $this->render('back/signalement_without_address/index.html.twig', [
            'form' => $form,
            'searchSignalementWithoutAddress' => $searchSignalementWithoutAddress,
            'rows' => $rows,
            'total' => $total,
            'pages' => (int) ceil($total / $this->maxListPagination),
        ]);
    }

    #[Route('/export', name: 'back_signalement_without_address_export', methods: 'GET')]
    public function export(Request $request): StreamedResponse
    {
        $searchSignalementWithoutAddress = new SearchSignalementWithoutAddress();
        $form = $this->createForm(SearchSignalementWithoutAddressType::class, $searchSignalementWithoutAddress);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchSignalementWithoutAddress = new SearchSignalementWithoutAddress();
        }

        $signalements = $this->signalementRepository->findSignalementsWithoutAddress($searchSignalementWithoutAddress);

        $response = new StreamedResponse(function () use ($signalements): void {
            $writer = new CsvWriter(new CsvOptions(FIELD_DELIMITER: ExportFormat::CSV_SEPARATOR));
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues([
                'Territoire',
                'Référence',
                'Adresse',
                'Code postal',
                'Ville',
                'Code INSEE',
                'Territoire calculé',
                'Erreur(s)',
                'Statut',
                'Importé ?',
                'Lien vers la fiche signalement',
            ]));
            foreach ($signalements as $signalement) {
                $calculatedTerritory = $this->signalementAddressAnomalyChecker->getCalculatedTerritory($signalement);
                $errors = $this->signalementAddressAnomalyChecker->getErrors($signalement);
                $writer->addRow(Row::fromValues([
                    $signalement->getTerritory()?->getZipAndName(),
                    $signalement->getReference(),
                    $signalement->getAdresseOccupant(),
                    $signalement->getCpOccupant(),
                    $signalement->getVilleOccupant(),
                    $signalement->getInseeOccupant(),
                    $calculatedTerritory?->getZipAndName(),
                    implode(', ', array_map(static fn ($error) => $error->label(), $errors)),
                    $signalement->getStatut()?->label(),
                    $signalement->getIsImported() ? 'Oui' : 'Non',
                    $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]));
            }
            $writer->close();
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="signalements-sans-adresse_'.date('Y-m-d_H-i-s').'.csv"');

        return $response;
    }

    #[Route('/{uuid:signalement}/rechercher-adresse', name: 'back_signalement_without_address_search', methods: 'GET')]
    public function searchAddress(Signalement $signalement, AddressService $addressService): JsonResponse
    {
        $adresseOccupant = str_replace(['-', '(s)', '(x)'], '', $signalement->getAdresseOccupant());
        $villeOccupant = (!empty($signalement->getCpOccupant()) ? $signalement->getCpOccupant().' ' : '').$signalement->getVilleOccupant();
        $query = trim($adresseOccupant.' '.$villeOccupant);
        if ('' === $query) {
            return $this->json(['query' => '', 'results' => []]);
        }

        $data = $addressService->searchAddress($query, 10);
        $features = $data['features'] ?? [];

        $territoryZip = $signalement->getTerritory()?->getZip();
        if ($territoryZip) {
            $features = array_values(array_filter(
                $features,
                static fn (array $feature) => str_starts_with((string) ($feature['properties']['citycode'] ?? ''), $territoryZip)
            ));
        }

        return $this->json(['query' => $query, 'results' => $features]);
    }

    #[Route('/{uuid:signalement}/change-territoire', name: 'back_signalement_change_territory', methods: 'POST')]
    public function changeTerritory(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('signalement_change_territory_'.$signalement->getId(), $token)) {
            return $this->json(['stayOnPage' => true, 'closeModal' => true, 'flashMessages' => [['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF]]]);
        }

        $calculatedTerritory = $this->signalementAddressAnomalyChecker->getCalculatedTerritory($signalement);
        if (null === $calculatedTerritory || !$calculatedTerritory->isIsActive()) {
            return $this->json(['stayOnPage' => true, 'flashMessages' => [['type' => 'alert', 'title' => 'Erreur', 'message' => 'Territoire invalide ou inactif.']]]);
        }

        $signalement->setTerritory($calculatedTerritory);
        $entityManager->flush();
        // TODO : faire un suivi ?

        return $this->json(['stayOnPage' => true, 'closeModal' => true, 'flashMessages' => [['type' => 'success', 'title' => 'Territoire changé', 'message' => 'Le territoire du signalement a bien été changé.']], 'htmlTargetContents' => $this->getHtmlTargetContentsForList($request)]);
    }

    #[Route('/{uuid:signalement}/lier-adresse', name: 'back_signalement_without_address_link', methods: 'POST')]
    public function linkAddress(
        Signalement $signalement,
        Request $request,
        SignalementAddressUpdater $signalementAddressUpdater,
        SuiviDelayedFactory $suiviDelayedFactory,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('signalement_link_address_'.$signalement->getId(), $token)) {
            return $this->json(['stayOnPage' => true, 'closeModal' => true, 'flashMessages' => [['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF]]]);
        }

        $rawFeature = json_decode((string) $request->request->get('feature', ''), true);
        if (!\is_array($rawFeature) || empty($rawFeature['properties']['citycode']) || empty($rawFeature['properties']['postcode'])) {
            return $this->json(['stayOnPage' => true, 'flashMessages' => [['type' => 'alert', 'title' => 'Erreur', 'message' => 'Adresse invalide.']]]);
        }

        $banAddress = new BanAddressResponse(['features' => [$rawFeature]]);

        try {
            $signalementAddressUpdater->attachAddressToSignalementFromBanAddress($signalement, $banAddress);
        } catch (TerritoryNotFoundForCityCodeException $exception) {
            return $this->json(['stayOnPage' => true, 'flashMessages' => [['type' => 'alert', 'title' => 'Erreur', 'message' => $exception->getMessage()]]]);
        }
        // TODO : changer l'adresse occupant dans l'entité signalement ?

        /** @var User $user */
        $user = $this->getUser(); // TODO : mettre utilisateur système
        $suiviDelayed = $suiviDelayedFactory->createSuiviDelayed(
            user: $user,
            signalement: $signalement,
            type: SuiviDelayedType::BO_EDIT_ADDRESS,
            category: SuiviCategory::SIGNALEMENT_EDITED_BO,
        );
        $entityManager->persist($suiviDelayed);
        $entityManager->flush();

        return $this->json([
            'stayOnPage' => true,
            'closeModal' => true,
            'flashMessages' => [['type' => 'success', 'title' => 'Adresse liée', 'message' => 'L\'adresse a bien été renseignée pour ce signalement.']],
            'htmlTargetContents' => $this->getHtmlTargetContentsForList($request),
        ]);
    }

    /**
     * @return array{0: FormInterface<mixed>, 1: SearchSignalementWithoutAddress, 2: array<int, array<string, mixed>>, 3: int}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        $searchSignalementWithoutAddress = new SearchSignalementWithoutAddress();
        $form = $this->createForm(SearchSignalementWithoutAddressType::class, $searchSignalementWithoutAddress);
        FormHelper::handleFormSubmitFromRequestOrSearchParams($form, $request, $fromSearchParams);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchSignalementWithoutAddress = new SearchSignalementWithoutAddress();
        }
        $paginatedSignalements = $this->signalementRepository->findSignalementsWithoutAddressPaginated($searchSignalementWithoutAddress, $this->maxListPagination);

        $rows = [];
        foreach ($paginatedSignalements as $signalement) {
            $rows[] = [
                'signalement' => $signalement,
                'calculatedTerritory' => $this->signalementAddressAnomalyChecker->getCalculatedTerritory($signalement),
                'errors' => $this->signalementAddressAnomalyChecker->getErrors($signalement),
            ];
        }

        return [$form, $searchSignalementWithoutAddress, $rows, $paginatedSignalements->count()];
    }

    /**
     * @return array<array{target: string, content: string}>
     */
    private function getHtmlTargetContentsForList(Request $request): array
    {
        [, $searchSignalementWithoutAddress, $rows, $total] = $this->handleSearch($request, true);

        return [
            [
                'target' => '#title-and-table-list-results',
                'content' => $this->renderView('back/signalement_without_address/_title-and-table-list-results.html.twig', [
                    'searchSignalementWithoutAddress' => $searchSignalementWithoutAddress,
                    'rows' => $rows,
                    'total' => $total,
                    'pages' => (int) ceil($total / $this->maxListPagination),
                ]),
            ],
        ];
    }
}
