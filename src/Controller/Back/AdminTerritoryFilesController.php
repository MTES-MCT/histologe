<?php

namespace App\Controller\Back;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\User;
use App\Form\SearchTerritoryFilesType;
use App\Form\TerritoryFileType;
use App\Messenger\Message\FileDuplicationMessage;
use App\Repository\FileRepository;
use App\Security\Voter\FileVoter;
use App\Service\FormHelper;
use App\Service\ImageManipulationHandler;
use App\Service\ListFilters\SearchTerritoryFiles;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/gerer-territoire/documents-types')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class AdminTerritoryFilesController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_NEW_DOCUMENT_SPACE')]
        private readonly bool $featureNewDocumentSpace,
        private readonly MessageBusInterface $messageBus,
    ) {
        if (!$this->featureNewDocumentSpace) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_territory_management_document', methods: ['GET'])]
    public function index(
        Request $request,
        FileRepository $fileRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchTerritoryFiles = new SearchTerritoryFiles($user);
        $form = $this->createForm(SearchTerritoryFilesType::class, $searchTerritoryFiles);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchTerritoryFiles = new SearchTerritoryFiles($user);
        }

        $territories = null;
        if (!$this->isGranted('ROLE_ADMIN')) {
            $territories = $user->getPartnersTerritories();
        }
        /** @var Paginator $paginatedFiles */
        $paginatedFiles = $fileRepository->findFilteredPaginated($searchTerritoryFiles, $territories, $maxListPagination);

        return $this->render('back/admin-territory-files/index.html.twig', [
            'form' => $form,
            'searchTerritoryFiles' => $searchTerritoryFiles,
            'files' => $paginatedFiles,
            'pages' => (int) ceil($paginatedFiles->count() / $maxListPagination),
        ]);
    }

    #[Route('/ajouter', name: 'back_territory_management_document_add', methods: ['GET'])]
    public function add(): Response
    {
        $file = new File();
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $file->setTerritory($user->getFirstTerritory());
        }

        /** @var Form $form */
        $form = $this->createForm(TerritoryFileType::class, $file, ['action' => $this->generateUrl('back_territory_management_document_add_ajax')]);

        return $this->render('back/admin-territory-files/add.html.twig', [
            'addForm' => $form,
        ]);
    }

    #[Route('/ajouter-ajax', name: 'back_territory_management_document_add_ajax', methods: ['POST'])]
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
        $form = $this->createForm(TerritoryFileType::class, $file, ['action' => $this->generateUrl('back_territory_management_document_add_ajax')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('file')->getData();

            if ($this->uploadFile($uploadedFile, $file, $user, $form, $fileScanner, $uploadHandlerService, $logger, $imageManipulationHandler)) {
                $em->persist($file);
                $em->flush();
                $successMessage = 'Le document a bien été ajouté.';

                // Dispatch message to duplicate file to all territories
                if (empty($file->getTerritory())) {
                    $this->messageBus->dispatch(new FileDuplicationMessage($file->getId()));
                    $successMessage = 'Le document a bien été ajouté. ';
                    if (DocumentType::GRILLE_DE_VISITE === $file->getDocumentType()) {
                        $successMessage .= 'Il va être dupliqué pour tous les territoires qui ne possèdent pas de grille de visite. Cela peut prendre quelques minutes.';
                    } else {
                        $successMessage .= 'Il va être dupliqué pour tous les territoires, cela peut prendre quelques minutes.';
                    }
                }

                $this->addFlash('success', $successMessage);

                $url = $this->generateUrl('back_territory_management_document');

                return $this->json(['redirect' => true, 'url' => $url]);
            }
        }

        $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm($form)];

        return $this->json($response, $response['code']);
    }

    #[Route('/editer/{file}', name: 'back_territory_management_document_edit', methods: ['GET'])]
    public function edit(
        File $file,
    ): Response {
        $this->denyAccessUnlessGranted(FileVoter::FILE_EDIT_DOCUMENT, $file);
        $form = $this->createForm(TerritoryFileType::class, $file, [
            'action' => $this->generateUrl('back_territory_management_document_edit_ajax', ['file' => $file->getId()]),
        ]);

        return $this->render('back/admin-territory-files/edit.html.twig', [
            'editForm' => $form,
            'file' => $file,
        ]);
    }

    #[Route('/editer-ajax/{file}', name: 'back_territory_management_document_edit_ajax', methods: ['POST'])]
    public function editAjax(
        File $file,
        Request $request,
        EntityManagerInterface $em,
        UploadHandlerService $uploadHandlerService,
        FileScanner $fileScanner,
        LoggerInterface $logger,
        ImageManipulationHandler $imageManipulationHandler,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(FileVoter::FILE_EDIT_DOCUMENT, $file);

        /** @var Form $form */
        $form = $this->createForm(TerritoryFileType::class, $file, [
            'action' => $this->generateUrl('back_territory_management_document_edit_ajax', ['file' => $file->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $flush = true;

            if (!empty($form->get('file')->getData())) {
                $flush = false;
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $form->get('file')->getData();

                /** @var User $user */
                $user = $this->getUser();
                $oldFilename = $file->getFilename();
                if ($this->uploadFile($uploadedFile, $file, $user, $form, $fileScanner, $uploadHandlerService, $logger, $imageManipulationHandler)) {
                    $flush = true;
                    $uploadHandlerService->deleteFileInBucketFromFilename($oldFilename);
                }
            }

            if ($flush) {
                $file->setDescription($file->getDescription());
                $em->flush();
                $this->addFlash('success', 'Le document a bien été modifié.');

                $url = $this->generateUrl('back_territory_management_document');

                return $this->json(['redirect' => true, 'url' => $url]);
            }
        }

        $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm($form)];

        return $this->json($response, $response['code']);
    }

    #[Route('/supprimer/{file}', name: 'back_territory_management_document_delete_ajax', methods: ['GET'])]
    public function deleteAjax(
        File $file,
        Request $request,
        UploadHandlerService $uploadHandlerService,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(FileVoter::FILE_DELETE_DOCUMENT, $file);
        if (!$this->isCsrfTokenValid('document_delete', $request->query->get('_token'))) {
            $this->addFlash('error', 'Le token CSRF est invalide.');
        } else {
            $uploadHandlerService->deleteFile($file);
            $this->addFlash('success', 'Le fichier a bien été supprimé.');
        }

        return $this->redirectToRoute('back_territory_management_document');
    }

    private function uploadFile(
        UploadedFile $uploadedFile,
        File $file,
        User $user,
        Form $form,
        FileScanner $fileScanner,
        UploadHandlerService $uploadHandlerService,
        LoggerInterface $logger,
        ImageManipulationHandler $imageManipulationHandler,
    ): bool {
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
                if ($file->getTerritory()) {
                    $file->setPartner($user->getPartnerInTerritoryOrFirstOne($file->getTerritory()));
                } elseif ($user->getPartners()->first()) {
                    $file->setPartner($user->getPartners()->first());
                }

                return true;
            } catch (FileException $e) {
                $logger->error($e->getMessage());
                $form->get('file')->addError(new FormError('Échec du téléchargement du document.'));
            }
        }

        return false;
    }
}
