<?php

namespace App\Controller\Back;

use App\Dto\Request\CalendarEventRequest;
use App\Entity\Enum\InterventionType;
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

        $operators = [];
        if ($user->isTerritoryAdmin()) {
            $partners = $partnerRepository->findPartnersWithQualificationOnTerritory(Qualification::VISITES, $user->getFirstTerritory());
            foreach ($partners as $partner) {
                $operators[] = ['id' => (string) $partner->getId(), 'label' => mb_strtoupper($partner->getNom())];
            }
            $operators[] = ['id' => 'extern', 'label' => 'Opérateur externe'];
        }

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

    #[Route('/events/', name: 'back_calendar_events_json', methods: ['POST'])]
    public function events(
        ClubEventRepository $clubEventRepository,
        InterventionRepository $interventionRepository,
        #[MapRequestPayload] ?CalendarEventRequest $calendarEventRequest = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

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
            $type = InterventionType::VISITE_CONTROLE === $intervention->getType() ? 'visite_controle' : 'visite';
            $title = InterventionType::VISITE_CONTROLE === $intervention->getType() ? 'Visite de contrôle #' : 'Visite #';
            $events[] = [
                'title' => $title.$intervention->getSignalement()->getReference(),
                'start' => $intervention->getScheduledAt()->format('Y-m-d\TH:i:s'),
                'extendedProps' => [
                    'type' => $type,
                    'link' => $this->generateUrl('back_signalement_view', ['uuid' => $intervention->getSignalement()->getUuid()]),
                    'occupant' => $intervention->getSignalement()->getNomOccupantComplet(),
                    'operator' => $intervention->getPartner()?->getNom(),
                ],
            ];
        }

        return $this->json($events);
    }
}
