<?php

namespace App\Controller\Back;

use App\Entity\Bailleur;
use App\Form\BailleurType;
use App\Form\SearchBailleurType;
use App\Repository\BailleurRepository;
use App\Service\ListFilters\SearchBailleur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/bailleur')]
class BackBailleurController extends AbstractController
{
    #[Route('/', name: 'back_bailleur_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        BailleurRepository $bailleurRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        $searchBailleur = new SearchBailleur();
        $form = $this->createForm(SearchBailleurType::class, $searchBailleur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchBailleur = new SearchBailleur();
        }
        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $paginatedBailleurs = $bailleurRepository->findFilteredPaginated($searchBailleur, $maxListPagination);

        return $this->render('back/bailleur/index.html.twig', [
            'form' => $form,
            'searchBailleur' => $searchBailleur,
            'bailleurs' => $paginatedBailleurs,
            'pages' => (int) ceil($paginatedBailleurs->count() / $maxListPagination),
        ]);
    }

    #[Route('/editer/{bailleur}', name: 'back_bailleur_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Bailleur $bailleur,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $form = $this->createForm(BailleurType::class, $bailleur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le bailleur a bien été modifié.');

            return $this->redirectToRoute('back_bailleur_edit', ['bailleur' => $bailleur->getId()]);
        }

        return $this->render('back/bailleur/edit.html.twig', [
            'form' => $form,
            'bailleur' => $bailleur,
        ]);
    }

    #[Route('/supprimer/{bailleur}', name: 'back_bailleur_delete', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Bailleur $bailleur,
        Request $request,
        EntityManagerInterface $em,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('bailleur_delete', $request->query->get('_token'))) {
            $this->addFlash('error', 'Le token CSRF est invalide.');

            return $this->redirectToRoute('back_bailleur_index');
        }
        if ($nb = $bailleur->getSignalements()->count() > 0) {
            $this->addFlash('error', 'Le bailleur ne peut pas être supprimé car il est lié à '.$nb.' signalements.');

            return $this->redirectToRoute('back_bailleur_index');
        }
        foreach ($bailleur->getBailleurTerritories() as $bailleurTerritory) {
            $em->remove($bailleurTerritory);
        }
        $em->remove($bailleur);
        $em->flush();
        $this->addFlash('success', 'Le bailleur a bien été supprimé.');

        return $this->redirectToRoute('back_bailleur_index');
    }
}
