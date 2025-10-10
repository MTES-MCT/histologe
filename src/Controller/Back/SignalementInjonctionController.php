<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\SearchSignalementInjonctionType;
use App\Repository\SignalementRepository;
use App\Security\Voter\UserVoter;
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
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted(UserVoter::SEE_INJONCTION_BAILLEUR, $user);

        $searchSignalementInjonction = new SearchSignalementInjonction($user);
        $form = $this->createForm(SearchSignalementInjonctionType::class, $searchSignalementInjonction);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchSignalementInjonction = new SearchSignalementInjonction($user);
        }
        $paginatedSignalementInjonction = $signalementRepository->findInjonctionFilteredPaginated($searchSignalementInjonction, $maxListPagination);

        return $this->render('back/signalement-injonction/index.html.twig', [
            'form' => $form,
            'searchSignalement' => $searchSignalementInjonction,
            'signalements' => $paginatedSignalementInjonction,
            'pages' => (int) ceil($paginatedSignalementInjonction->count() / $maxListPagination),
        ]);
    }
}
