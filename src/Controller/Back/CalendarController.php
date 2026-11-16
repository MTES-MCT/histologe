<?php

namespace App\Controller\Back;

use App\Entity\Enum\Qualification;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Dto\Request\CalendarEventRequest;
use App\Repository\ClubEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

#[Route('/bo/calendrier')]
class CalendarController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_CALENDAR')]
        private readonly bool $featureCalendar,
    ) {
        if (!$this->featureCalendar) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_calendar', methods: ['GET'])]
    public function index(
        PartnerRepository $partnerRepository,
        TerritoryRepository $territoryRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Filtre "opérateur" réservé au RT (un seul territoire) : partenaires ayant la compétence visites
        // + une entrée pour les visites faites par un opérateur externe
        $operators = [];
        $userTerritory = $user->getFirstTerritory();
        if ($user->isTerritoryAdmin() && null !== $userTerritory) {
            $partners = $partnerRepository->findPartnersWithQualificationOnTerritory(Qualification::VISITES, $userTerritory);
            foreach ($partners as $partner) {
                $operators[] = ['id' => (string) $partner->getId(), 'label' => mb_strtoupper($partner->getNom())];
            }
            $operators[] = ['id' => 'extern', 'label' => 'Opérateur externe'];
        }

        // Filtre "territoire" : tous les territoires actifs pour le SA,
        // ceux de l'utilisateur s'il est rattaché à des partenaires de plusieurs territoires
        $territories = [];
        if ($user->isSuperAdmin()) {
            $territories = $territoryRepository->findAllList();
        } elseif ($user->isMultiTerritoire()) {
            $territories = $user->getPartnersTerritories();
        }
        $territoryOptions = [];
        foreach ($territories as $territory) {
            $territoryOptions[] = ['id' => $territory->getId(), 'label' => $territory->getZipAndName()];
        }

        return $this->render('back/calendar/index.html.twig', [
            'operators' => $operators,
            'territories' => $territoryOptions,
        ]);
    }

    #[Route('/events/', name: 'back_calendar_events')]
    public function events(
        ClubEventRepository $clubEventRepository,
        // AddressesHistoryListViewFactory $addressesHistoryListViewFactory,
        // ZoneRepository $zoneRepository,
        #[MapQueryString] ?CalendarEventRequest $calendarEventRequest = null,
    ): void {
        



        /** @var User $user */
        $user = $this->getUser();

        // $filters = null !== $calendarEventRequest
        //     ? $calendarEventRequest->getFilters()
        //     : [
        //     ];



        $clubEvents = $clubEventRepository->findAllFilteredForUser($user, $calendarEventRequest);
        // $visites = [];
        // $processedSignalements = [];
        // $processedArretes = [];

        // TODO : en sortie : liste d'événements 
        // si visite : date, heure, référence signalement, uuis signalement, Nom et prénom occupant, nom opérateur
        // si club : date, nom club


        // $responseData = [
        //     'filters' => $filters,
        //     'list' => array_values($responseAddresses),
        //     'pagination' => [
        //         'current_page' => $page,
        //         'total_pages' => $totalPages,
        //         'total_items' => $totalAddresses,
        //     ],
        //     'zoneAreas' => $zoneAreas,
        // ];

        // $response = $this->json(
        //     $responseData,
        //     Response::HTTP_OK,
        //     ['content-type' => 'application/json'],
        //     ['groups' => ['signalements:read']]
        // );

        // // Remove '?' at the start of the string
        // $parsableQueryString = null !== $addressesHistorySearchQuery
        //     ? substr($addressesHistorySearchQuery->getQueryStringForUrl(), 1)
        //     : '';
        // $cookie = Cookie::create(AddressesHistorySearchQuery::COOKIE_NAME)
        //     ->withValue($parsableQueryString)
        //     ->withExpires(strtotime('+1 hour'));

        // $response->headers->setCookie($cookie);

        // return $response;
    }
}
