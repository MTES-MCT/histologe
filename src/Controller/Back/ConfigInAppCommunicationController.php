<?php

namespace App\Controller\Back;

use App\Entity\InAppCommunication;
use App\Form\InAppCommunicationType;
use App\Form\SearchInAppCommunicationType;
use App\Repository\InAppCommunicationRepository;
use App\Service\ListFilters\SearchInAppCommunication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/config-in-app-communication')]
#[IsGranted('ROLE_ADMIN')]
final class ConfigInAppCommunicationController extends AbstractController
{
    public const array COMMUNICATION_TYPES = [
        'info' => 'Information',
        'warning' => 'Warning',
        'alert' => 'Alerte',
    ];

    #[Route('/', name: 'back_config_in_app_communication_index')]
    public function index(
        InAppCommunicationRepository $inAppCommunicationRepository,
        Request $request,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        $searchInAppCommunication = new SearchInAppCommunication();
        $form = $this->createForm(SearchInAppCommunicationType::class, $searchInAppCommunication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $searchInAppCommunication = new SearchInAppCommunication();
        }

        $paginatedInAppCommunications = $inAppCommunicationRepository->findFilteredPaginated($searchInAppCommunication, $maxListPagination);

        return $this->render('back/in-app-communication/index.html.twig', [
            'form' => $form,
            'inAppCommunications' => $paginatedInAppCommunications,
            'searchInAppCommunication' => $searchInAppCommunication,
            'pages' => (int) ceil($paginatedInAppCommunications->count() / $maxListPagination),
        ]);
    }

    #[Route(path: '/ajouter', name: 'back_config_in_app_communication_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $inAppCommunication = new InAppCommunication();
        $form = $this->createForm(InAppCommunicationType::class, $inAppCommunication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($inAppCommunication);
            $entityManager->flush();

            $this->addFlash('success', ['title' => 'Nouvelle communication', 'message' => 'La communication a bien été ajoutée.']);

            return $this->redirectToRoute('back_config_in_app_communication_index');
        }

        return $this->render('back/in-app-communication/add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/editer/{id}', name: 'back_config_in_app_communication_edit', methods: ['GET', 'POST'])]
    public function edit(
        InAppCommunication $inAppCommunication,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(InAppCommunicationType::class, $inAppCommunication);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', ['title' => 'Communication modifiée',
                'message' => 'La communication a bien été modifiée.',
            ]);

            return $this->redirectToRoute('back_config_in_app_communication_index');
        }

        return $this->render('back/in-app-communication/edit.html.twig', [
            'form' => $form,
            'inAppCommunication' => $inAppCommunication,
        ]);
    }

    #[Route('/supprimer/{id}', name: 'back_config_in_app_communication_delete', methods: ['POST'])]
    public function delete(
        InAppCommunication $inAppCommunication,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('in_app_communication_delete', $token)) {
            $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez actualiser la page et réessayer.');

            return $this->redirectToRoute('back_config_in_app_communication_index');
        }
        $title = $inAppCommunication->getTitle();
        $entityManager->remove($inAppCommunication);
        $entityManager->flush();
        $this->addFlash('success', ['title' => 'Communication supprimée',
            'message' => 'La communication "'.$title.'" a bien été supprimée.',
        ]);

        return $this->redirectToRoute('back_config_in_app_communication_index');
    }
}
