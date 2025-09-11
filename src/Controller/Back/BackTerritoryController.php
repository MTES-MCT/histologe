<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Form\SearchTerritoryType;
use App\Form\TerritoryType;
use App\Repository\BailleurRepository;
use App\Repository\TerritoryRepository;
use App\Security\Voter\TerritoryVoter;
use App\Service\ListFilters\SearchTerritory;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/territoires')]
class BackTerritoryController extends AbstractController
{
    #[Route('/', name: 'back_territories_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        TerritoryRepository $territoryRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
        #[Autowire(env: 'FEATURE_NEW_DOCUMENT_SPACE')] bool $featureNewDocumentSpace,
    ): Response {
        if ($featureNewDocumentSpace) {
            throw $this->createNotFoundException();
        }

        $searchTerritory = new SearchTerritory();
        $form = $this->createForm(SearchTerritoryType::class, $searchTerritory);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchTerritory = new SearchTerritory();
        }
        $paginatedTerritories = $territoryRepository->findFilteredPaginated($searchTerritory, $maxListPagination);

        return $this->render('back/territory/index.html.twig', [
            'form' => $form,
            'searchTerritory' => $searchTerritory,
            'territories' => $paginatedTerritories,
            'pages' => (int) ceil($paginatedTerritories->count() / $maxListPagination),
        ]);
    }

    #[Route('/{territory}', name: 'back_territories_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Territory $territory,
        Request $request,
        FileScanner $fileScanner,
        UploadHandlerService $uploadHandlerService,
        EntityManagerInterface $em,
        #[Autowire(env: 'FEATURE_NEW_DOCUMENT_SPACE')] bool $featureNewDocumentSpace,
    ): Response {
        if ($featureNewDocumentSpace) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TerritoryType::class, $territory);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('grilleVisite')->getData();
            $deleteFile = false;
            if ($form->has('deleteGrilleVisite')) {
                $deleteFile = $form->get('deleteGrilleVisite')->getData();
            }
            if ($file) {
                if (!$fileScanner->isClean($file->getPathname())) {
                    $this->addFlash('error', 'Par mesure de sécurité, le fichier '.$file->getClientOriginalName().' a été rejeté car il contient du code exécutable.');

                    return $this->redirectToRoute('back_territories_edit', ['territory' => $territory->getId()]);
                }
                if ($territory->getGrilleVisiteFilename()) {
                    $uploadHandlerService->deleteSingleFile($territory->getGrilleVisiteFilename());
                }
                $filename = 'grille-visite-'.$territory->getId().'-'.uniqid().'-.pdf';
                $uploadHandlerService->uploadFromFile($file, $filename);
                $territory->setGrilleVisiteFilename($filename);
            } elseif ($deleteFile) {
                $uploadHandlerService->deleteSingleFile($territory->getGrilleVisiteFilename());
                $territory->setGrilleVisiteFilename(null);
            }
            $em->flush();
            $this->addFlash('success', 'Le territoire a bien été modifié.');

            return $this->redirectToRoute('back_territories_edit', ['territory' => $territory->getId()]);
        }

        return $this->render('back/territory/edit.html.twig', [
            'form' => $form,
            'territory' => $territory,
        ]);
    }

    #[Route('/{territory}/grille-visite', name: 'back_territory_grille_visite', methods: ['GET'])]
    public function grilleVisite(
        Territory $territory,
        #[Autowire(env: 'FEATURE_NEW_DOCUMENT_SPACE')] bool $featureNewDocumentSpace,
    ): BinaryFileResponse {
        if ($featureNewDocumentSpace) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(TerritoryVoter::GET_DOCUMENT, $territory);

        if ($territory->getIsGrilleVisiteDisabled()) {
            throw $this->createNotFoundException();
        }

        $filename = $territory->getGrilleVisiteFilename();
        if ($filename) {
            $tmpFilepath = $this->getParameter('uploads_tmp_dir').$filename;
            $bucketFilepath = $this->getParameter('url_bucket').'/'.$filename;
            $content = file_get_contents($bucketFilepath);
            file_put_contents($tmpFilepath, $content);
            $file = new SymfonyFile($tmpFilepath);

            return new BinaryFileResponse($file);
        }

        $filePath = $this->getParameter('file_dir').'Grille-visite.pdf';
        $file = new SymfonyFile($filePath);

        return new BinaryFileResponse($file);
    }

    #[Route('/{territory}/bailleurs', name: 'back_territory_bailleurs', methods: ['GET'])]
    public function bailleursByTerritory(
        Territory $territory,
        BailleurRepository $bailleurRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(TerritoryVoter::GET_BAILLEURS_LIST, $territory);
        /** @var User $user */
        $user = $this->getUser();
        $bailleurs = $bailleurRepository->findBailleursByTerritory($user, $territory);

        return $this->json($bailleurs);
    }
}
