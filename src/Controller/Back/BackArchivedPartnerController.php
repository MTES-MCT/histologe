<?php

namespace App\Controller\Back;

use App\Form\SearchArchivedPartnerType;
use App\Repository\PartnerRepository;
use App\Service\ListFilters\SearchArchivedPartner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/partner-archives')]
class BackArchivedPartnerController extends AbstractController
{
    #[Route('/', name: 'back_archived_partner_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        PartnerRepository $partnerRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        $searchArchivedPartner = new SearchArchivedPartner();
        $form = $this->createForm(SearchArchivedPartnerType::class, $searchArchivedPartner);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchArchivedPartner = new SearchArchivedPartner();
        }
        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $paginatedArchivedPartners = $partnerRepository->findFilteredArchivedPaginated($searchArchivedPartner, $maxListPagination);

        return $this->render('back/partner_archived/index.html.twig', [
            'form' => $form,
            'searchArchivedPartner' => $searchArchivedPartner,
            'archivedPartners' => $paginatedArchivedPartners,
            'pages' => (int) ceil($paginatedArchivedPartners->count() / $maxListPagination),
        ]);
    }
}
