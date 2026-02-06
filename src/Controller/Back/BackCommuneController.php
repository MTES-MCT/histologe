<?php

namespace App\Controller\Back;

use App\Entity\Commune;
use App\Form\CommuneType;
use App\Form\SearchCommuneType;
use App\Repository\CommuneRepository;
use App\Repository\SignalementRepository;
use App\Service\FormHelper;
use App\Service\Gouv\Ban\AddressService;
use App\Service\ListFilters\SearchCommune;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/commune')]
#[IsGranted('ROLE_ADMIN')]
class BackCommuneController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
        private readonly CommuneRepository $communeRepository,
    ) {
    }

    /**
     * @return array{FormInterface, SearchCommune, Paginator<Commune>}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        $searchCommune = new SearchCommune();
        $form = $this->createForm(SearchCommuneType::class, $searchCommune);
        FormHelper::handleFormSubmitFromRequestOrSearchParams($form, $request, $fromSearchParams);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchCommune = new SearchCommune();
        }
        /** @var Paginator<Commune> $paginatedCommunes */
        $paginatedCommunes = $this->communeRepository->findFilteredPaginated($searchCommune, $this->maxListPagination);

        return [$form, $searchCommune, $paginatedCommunes];
    }

    #[Route('/', name: 'back_commune_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        [$form, $searchCommune, $paginatedCommunes] = $this->handleSearch($request);

        return $this->render('back/commune/index.html.twig', [
            'form' => $form,
            'searchCommune' => $searchCommune,
            'communes' => $paginatedCommunes,
            'pages' => (int) ceil($paginatedCommunes->count() / $this->maxListPagination),
        ]);
    }

    #[Route('/editer/{commune}', name: 'back_commune_edit', methods: ['GET', 'POST'])]
    public function edit(
        Commune $commune,
        Request $request,
        SignalementRepository $signalementRepository,
        EntityManagerInterface $em,
        AddressService $addressService,
    ): Response {
        $poiCommune = $addressService->getMunicipalityByCityCode($commune->getNom(), $commune->getCodeInsee());
        $countSignalements = $signalementRepository->countForCommune($commune);
        $inconsistentSignalements = $signalementRepository->findWithInconsistentCommuneName($commune);
        $form = $this->createForm(CommuneType::class, $commune);
        $originalNom = $commune->getNom();
        $form->handleRequest($request);
        $nomUpdated = false;
        if ($form->isSubmitted() && $form->isValid()) {
            if ($originalNom !== $commune->getNom()) {
                $nomUpdated = true;
                // on refait la requete suite au changement de nom pour avoir la liste à jour des signalements à modifier
                $inconsistentSignalements = $signalementRepository->findWithInconsistentCommuneName($commune);
                foreach ($inconsistentSignalements as $signalement) {
                    $signalement->setVilleOccupant($commune->getNom());
                }
            }
            $em->flush();
            $message = 'La commune a bien été modifiée.';
            if ($nomUpdated && count($inconsistentSignalements) > 0) {
                $message .= sprintf(' %d signalement(s) ont été mis à jour pour être cohérents avec le nouveau nom de la commune.', count($inconsistentSignalements));
            }
            $this->addFlash('success', ['title' => 'Modifications enregistrées', 'message' => $message]);

            return $this->redirectToRoute('back_commune_edit', ['commune' => $commune->getId()]);
        }

        return $this->render('back/commune/edit.html.twig', [
            'form' => $form,
            'commune' => $commune,
            'countSignalements' => $countSignalements,
            'inconsistentSignalements' => $inconsistentSignalements,
            'poiCommune' => $poiCommune,
        ]);
    }
}
