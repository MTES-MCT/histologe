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
use App\Utils\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

        /** @var User $user */
        $user = $this->getUser();
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
