<?php

namespace App\Controller\Back;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\User;
use App\Form\AddTerritoryFileType;
use App\Form\SearchTerritoryFilesType;
use App\Repository\FileRepository;
use App\Security\Voter\FileVoter;
use App\Service\FormHelper;
use App\Service\ImageManipulationHandler;
use App\Service\ListFilters\SearchTerritoryFiles;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/gerer-territoire/documents-types')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class AdminTerritoryFilesController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_NEW_DOCUMENT_SPACE')]
        private readonly bool $featureNewDocumentSpace,
    ) {
        if (!$this->featureNewDocumentSpace) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_territory_management_document', methods: ['GET'])]
    public function index(
        Request $request,
        FileRepository $fileRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchTerritoryFiles = new SearchTerritoryFiles($user);
        $form = $this->createForm(SearchTerritoryFilesType::class, $searchTerritoryFiles);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchTerritoryFiles = new SearchTerritoryFiles($user);
        }

        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $territory = null;
        if (!$this->isGranted('ROLE_ADMIN')) {
            $territory = $user->getFirstTerritory();
        }
        $paginatedFiles = $fileRepository->findFilteredPaginated($searchTerritoryFiles, $territory, $maxListPagination);

        return $this->render('back/admin-territory-files/index.html.twig', [
            'form' => $form,
            'searchTerritoryFiles' => $searchTerritoryFiles,
            'files' => $paginatedFiles,
            'pages' => (int) ceil($paginatedFiles->count() / $maxListPagination),
        ]);
    }

    #[Route('/ajouter', name: 'back_territory_management_document_add', methods: ['GET'])]
    public function addTerritoryFile(): Response
    {
        $file = new File();
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $file->setTerritory($user->getFirstTerritory());
        }

        /** @var Form $form */
        $form = $this->createForm(AddTerritoryFileType::class, $file, ['action' => $this->generateUrl('back_territory_management_document_add_ajax')]);

        return $this->render('back/admin-territory-files/add.html.twig', [
            'addForm' => $form,
        ]);
    }

    #[Route('/ajouter-ajax', name: 'back_territory_management_document_add_ajax', methods: 'POST')]
    public function addAjax(
        Request $request,
        EntityManagerInterface $em,
        UploadHandlerService $uploadHandlerService,
        FileScanner $fileScanner,
        LoggerInterface $logger,
        ImageManipulationHandler $imageManipulationHandler,
    ): JsonResponse {
        $file = new File();
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $file->setTerritory($user->getFirstTerritory());
        }

        /** @var Form $form */
        $form = $this->createForm(AddTerritoryFileType::class, $file, ['action' => $this->generateUrl('back_territory_management_document_add_ajax')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Check to add a single Grille de visite
            $existingVisitGrid = false;
            $documentType = $form->get('documentType')->getData();
            if ($documentType && DocumentType::GRILLE_DE_VISITE === $documentType) {
                $existingVisitGrid = $em->getRepository(File::class)->findOneBy([
                    'territory' => $file->getTerritory(),
                    'documentType' => DocumentType::GRILLE_DE_VISITE,
                ]);
                if ($existingVisitGrid) {
                    $form->get('documentType')->addError(new FormError('Une grille de visite existe déjà pour ce territoire. Vous ne pouvez en ajouter qu\'une seule.'));
                }
            }

            if (!$existingVisitGrid) {
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $form->get('file')->getData();

                if (!$fileScanner->isClean($uploadedFile->getPathname())) {
                    $form->get('file')->addError(new FormError('Le fichier est infecté'));
                } else {
                    try {
                        $res = $uploadHandlerService->toTempFolder($uploadedFile);

                        if (isset($res['error'])) {
                            throw new \Exception($res['error']);
                        }

                        $file->setFilename($res['file']);
                        // Move main file
                        $uploadHandlerService->moveFromBucketTempFolder($file->getFilename());
                        $variantsGenerated = false;
                        if (\in_array($uploadedFile->getMimeType(), File::RESIZABLE_MIME_TYPES)) {
                            $imageManipulationHandler->resize($file->getFilename())->thumbnail();
                            // Move variants
                            $uploadHandlerService->movePhotoVariants($file->getFilename());
                            $variantsGenerated = true;
                        }

                        $extension = strtolower(pathinfo($res['file'], \PATHINFO_EXTENSION));
                        $file->setExtension(strtolower($extension));
                        $file->setIsVariantsGenerated($variantsGenerated);
                        $file->setScannedAt(new \DateTimeImmutable());
                        $file->setIsStandalone(true);
                        $file->setUploadedBy($user);
                        $file->setDescription($file->getDescription());
                        $em->persist($file);
                        $em->flush();

                        $this->addFlash('success', 'Le document a bien été ajouté.');

                        $url = $this->generateUrl('back_territory_management_document');

                        return $this->json(['redirect' => true, 'url' => $url]);
                    } catch (FileException $e) {
                        $logger->error($e->getMessage());
                        $form->get('file')->addError(new FormError('Échec du téléchargement du document.'));
                    }
                }
            }
        }

        $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm($form)];

        return $this->json($response, $response['code']);
    }

    #[Route('/editer/{file}', name: 'back_territory_management_document_edit', methods: ['GET'])]
    public function edit(): Response
    {
        // TODO : prochain ticket
        $file = new File();
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $file->setTerritory($user->getFirstTerritory());
        }

        /** @var Form $form */
        $form = $this->createForm(AddTerritoryFileType::class, $file, ['action' => $this->generateUrl('back_territory_management_document_add_ajax')]);

        return $this->render('back/admin-territory-files/add.html.twig', [
            'addForm' => $form,
        ]);
    }

    #[Route('/supprimer/{file}', name: 'back_territory_management_document_delete_ajax', methods: ['GET'])]
    public function deleteAjax(
        File $file,
        Request $request,
        UploadHandlerService $uploadHandlerService,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(FileVoter::DELETE_DOCUMENT, $file);
        if (!$this->isCsrfTokenValid('document_delete', $request->query->get('_token'))) {
            $this->addFlash('error', 'Le token CSRF est invalide.');
        } else {
            $uploadHandlerService->deleteFile($file);
            $this->addFlash('success', 'Le fichier a bien été supprimé.');
        }

        return $this->redirectToRoute('back_territory_management_document');
    }
}
