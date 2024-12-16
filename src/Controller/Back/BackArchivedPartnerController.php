<?php

namespace App\Controller\Back;

use App\Form\SearchArchivedPartnerType;
use App\Repository\PartnerRepository;
use App\Service\SearchArchivedPartner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/partner-archives')]
class BackArchivedPartnerController extends AbstractController
{
    public const MAX_LIST_PAGINATION = 50;

    #[Route('/', name: 'back_archived_partner_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        PartnerRepository $partnerRepository,
    ): Response {
        [$form, $searchArchivedPartner, $paginatedArchivedPartners] = $this->handleSearchArchivedPartner($request, $partnerRepository);

        return $this->render('back/partner_archived/index.html.twig', [
            'form' => $form,
            'searchArchivedPartner' => $searchArchivedPartner,
            'archivedPartners' => $paginatedArchivedPartners,
            'pages' => (int) ceil($paginatedArchivedPartners->count() / self::MAX_LIST_PAGINATION),
        ]);
    }

    private function handleSearchArchivedPartner(Request $request, PartnerRepository $partnerRepository): array
    {
        $searchArchivedPartner = new SearchArchivedPartner($this->getUser());
        $form = $this->createForm(SearchArchivedPartnerType::class, $searchArchivedPartner);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchArchivedPartner = new SearchArchivedPartner($this->getUser());
        }
        $paginatedArchivedPartner = $partnerRepository->findFilteredArchivedPaginated($searchArchivedPartner, self::MAX_LIST_PAGINATION);

        return [$form, $searchArchivedPartner, $paginatedArchivedPartner];
    }
}
