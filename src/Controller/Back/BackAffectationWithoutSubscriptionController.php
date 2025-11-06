<?php

namespace App\Controller\Back;

use App\Form\SearchAffectationWithoutSubscriptionType;
use App\Repository\AffectationRepository;
use App\Service\ListFilters\SearchAffectationWithoutSubscription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/affectation-without-subscription')]
class BackAffectationWithoutSubscriptionController extends AbstractController
{
    #[Route('/', name: 'back_affectation_without_subscription_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        AffectationRepository $affectationRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        $searchAffectationWithoutSubscription = new SearchAffectationWithoutSubscription();
        $form = $this->createForm(SearchAffectationWithoutSubscriptionType::class, $searchAffectationWithoutSubscription);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchAffectationWithoutSubscription = new SearchAffectationWithoutSubscription();
        }
        $paginatedAffectations = $affectationRepository->findWithoutSubscriptionFilteredPaginated($searchAffectationWithoutSubscription, $maxListPagination);

        return $this->render('back/affectation-without-subscription/index.html.twig', [
            'form' => $form,
            'searchAffectation' => $searchAffectationWithoutSubscription,
            'affectations' => $paginatedAffectations,
            'pages' => (int) ceil($paginatedAffectations->count() / $maxListPagination),
        ]);
    }
}
