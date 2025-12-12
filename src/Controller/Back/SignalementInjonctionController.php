<?php

namespace App\Controller\Back;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Form\SearchSignalementInjonctionType;
use App\Repository\SignalementRepository;
use App\Security\Voter\InjonctionBailleurVoter;
use App\Service\InjonctionBailleur\CourrierBailleurGenerator;
use App\Service\ListFilters\SearchSignalementInjonction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalement-injonction')]
class SignalementInjonctionController extends AbstractController
{
    #[Route('/', name: 'back_injonction_signalement_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted(InjonctionBailleurVoter::INJONCTION_BAILLEUR_SEE);

        $searchSignalementInjonction = new SearchSignalementInjonction($user);
        $form = $this->createForm(SearchSignalementInjonctionType::class, $searchSignalementInjonction);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchSignalementInjonction = new SearchSignalementInjonction($user);
        }
        $userPartners = (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) ? $user->getPartners() : null;
        $paginatedSignalementInjonction = $signalementRepository->findInjonctionFilteredPaginated($searchSignalementInjonction, $maxListPagination, $userPartners);

        return $this->render('back/signalement-injonction/index.html.twig', [
            'form' => $form,
            'searchSignalement' => $searchSignalementInjonction,
            'signalements' => $paginatedSignalementInjonction,
            'pages' => (int) ceil($paginatedSignalementInjonction->count() / $maxListPagination),
        ]);
    }

    #[Route('/{uuid:signalement}/courrier-bailleur', name: 'back_injonction_signalement_courrier_bailleur', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function courrierBailleur(
        Signalement $signalement,
        CourrierBailleurGenerator $courrierBailleurGenerator,
    ): Response {
        if (SignalementStatus::INJONCTION_BAILLEUR !== $signalement->getStatut()) {
            throw $this->createAccessDeniedException();
        }

        $pdfContent = $courrierBailleurGenerator->generate($signalement);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="courrier-bailleur.pdf"');

        return $response;
    }
}
