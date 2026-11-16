<?php

namespace App\Controller\Back;

use App\Dto\Request\CalendarEventRequest;
use App\Entity\Enum\Qualification;
use App\Entity\User;
use App\Repository\ClubEventRepository;
use App\Repository\InterventionRepository;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

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
        InterventionRepository $interventionRepository,
        #[MapRequestPayload] ?CalendarEventRequest $calendarEventRequest = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        // TODO : ajouter un Voter ?

        $clubEvents = $clubEventRepository->findAllFilteredForUser($user, $calendarEventRequest);
        $interventions = $interventionRepository->findAllFilteredForUser($user, $calendarEventRequest);

        $events = [];
        foreach ($clubEvents as $clubEvent) {
            $events[] = [
                'title' => $clubEvent->getName(),
                'start' => $clubEvent->getDateEvent()->format('Y-m-d\TH:i:s'),
                'extendedProps' => [
                    'type' => 'club',
                    'link' => $clubEvent->getUrl(),
                ],
            ];
        }

        foreach ($interventions as $intervention) {
            $events[] = [
                'title' => 'Visite #'.$intervention->getSignalement()->getReference(),
                'start' => $intervention->getScheduledAt()->format('Y-m-d\TH:i:s'),
                'extendedProps' => [
                    'type' => 'visite', // TODO : préciser visite de contrôle ?
                    'link' => $this->generateUrl('back_signalement_view', ['uuid' => $intervention->getSignalement()->getUuid()]),
                    'occupant' => $intervention->getSignalement()->getNomOccupantComplet(),
                    'operator' => $intervention->getPartner()?->getNom(),
                ],
            ];
        }

        return $this->json($events);
    }
}
