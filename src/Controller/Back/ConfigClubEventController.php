<?php

namespace App\Controller\Back;

use App\Entity\ClubEvent;
use App\Entity\User;
use App\Form\ClubEventType;
use App\Form\SearchClubEventType;
use App\Repository\ClubEventRepository;
use App\Service\ListFilters\SearchClubEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/config-club-event')]
#[IsGranted('ROLE_ADMIN')]
final class ConfigClubEventController extends AbstractController
{
    #[Route('/', name: 'back_config_club_event_index')]
    public function index(
        ClubEventRepository $clubEventRepository,
        Request $request,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchClubEvent = new SearchClubEvent($user);
        $form = $this->createForm(SearchClubEventType::class, $searchClubEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $searchClubEvent = new SearchClubEvent($user);
        }

        $paginatedClubEvents = $clubEventRepository->findFilteredPaginated($searchClubEvent, $maxListPagination);

        return $this->render('back/config_club_event/index.html.twig', [
            'form' => $form,
            'clubEvents' => $paginatedClubEvents,
            'searchClubEvent' => $searchClubEvent,
            'pages' => (int) ceil($paginatedClubEvents->count() / $maxListPagination),
        ]);
    }

    #[Route(path: '/add', name: 'back_config_club_event_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $clubEvent = new ClubEvent();
        $form = $this->createForm(ClubEventType::class, $clubEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($clubEvent);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a bien été ajouté.');

            return $this->redirectToRoute('back_config_club_event_index');
        }

        return $this->render('back/config_club_event/add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/editer/{id}', name: 'back_club_event_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        ClubEvent $clubEvent,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(ClubEventType::class, $clubEvent);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'L\'événement a bien été modifié.');

            return $this->redirectToRoute('back_config_club_event_index');
        }

        return $this->render('back/config_club_event/edit.html.twig', [
            'form' => $form,
            'clubEvent' => $clubEvent,
        ]);
    }

    #[Route('/supprimer/{id}', name: 'back_config_club_event_delete', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        ClubEvent $clubEvent,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('club_event_delete', $request->query->get('_token'))) {
            $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez actualiser la page et réessayer.');

            return $this->redirectToRoute('back_config_club_event_index');
        }
        $name = $clubEvent->getName();
        $dateEvent = $clubEvent->getDateEvent();
        $entityManager->remove($clubEvent);
        $entityManager->flush();
        $this->addFlash('success', 'L\'événement "'.$name.'" du '.$dateEvent->format('d/m/Y H:i').' a bien été supprimé.');

        return $this->redirectToRoute('back_config_club_event_index');
    }

    #[Route('/copier/{id}', name: 'back_club_event_copy', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function copy(
        ClubEvent $clubEvent,
    ): Response {
        $form = $this->createForm(ClubEventType::class, $clubEvent, ['action' => $this->generateUrl('back_config_club_event_add')]);

        return $this->render('back/config_club_event/add.html.twig', [
            'form' => $form,
        ]);
    }
}
