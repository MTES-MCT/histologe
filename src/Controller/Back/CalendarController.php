<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
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
    public function index(): Response
    {
        return $this->render('back/calendar/index.html.twig');
    }
}
