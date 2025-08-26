<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\User;
use App\Form\AddTerritoryFileType;
use App\Form\SearchTerritoryFilesType;
use App\Repository\FileRepository;
use App\Service\ListFilters\SearchTerritoryFiles;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/documents-types')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class AdminTerritoryFilesController extends AbstractController
{
    #[Route('/', name: 'back_admin_territory_files_index', methods: ['GET'])]
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

    #[Route('/ajouter', name: 'back_admin_territory_files_add', methods: ['GET', 'POST'])]
    public function addTerritoryFile(
        Request $request,
        EntityManagerInterface $em,
        UploadHandlerService $uploadHandlerService,
        FileScanner $fileScanner,
        ValidatorInterface $validator,
        LoggerInterface $logger,
    ): Response {
        $file = new File();
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $file->setTerritory($user->getFirstTerritory());
        }

        /** @var Form $form */
        $form = $this->createForm(AddTerritoryFileType::class, $file, ['action' => $this->generateUrl('back_admin_territory_files_add')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('file')->getData();

            $errors = $validator->validate(
                $uploadedFile,
                [
                    new Assert\File(
                        maxSize: '10M',
                        mimeTypes: File::DOCUMENT_MIME_TYPES,
                        maxSizeMessage: 'Le fichier ne doit pas dépasser 10 Mo.',
                        mimeTypesMessage: 'Seuls les fichiers {{ types }} sont autorisés.'
                    ),
                ]
            );

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $form->get('file')->addError(new FormError($error->getMessage()));
                }
            } elseif (!$fileScanner->isClean($uploadedFile->getPathname())) {
                $form->get('file')->addError(new FormError('Le fichier est infecté'));
            } else {
                try {
                    $res = $uploadHandlerService->toTempFolder($uploadedFile);

                    if (isset($res['error'])) {
                        throw new \Exception($res['error']);
                    }

                    $uploadHandlerService->moveFilePath($res['filePath']);
                    $file->setFilename($res['file']);
                    $extension = strtolower(pathinfo($res['file'], \PATHINFO_EXTENSION));
                    $file->setExtension(strtolower($extension));
                    $file->setIsVariantsGenerated(true);
                    $file->setIsWaitingSuivi(false);
                    $file->setIsTemp(false);
                    $file->setIsOriginalDeleted(false);
                    $file->setIsStandalone(true);

                    $file->setUploadedBy($user);
                    $em->persist($file);
                    $em->flush();

                    $this->addFlash('success', 'Le document a bien été ajouté.');

                    return $this->redirectToRoute('back_admin_territory_files_index');
                } catch (FileException $e) {
                    $logger->error($e->getMessage());
                    $form->get('file')->addError(new FormError('Échec du téléchargement du document.'));
                }
            }
        }

        $this->displayErrors($form);

        return $this->render('back/admin-territory-files/add.html.twig', [
            'addForm' => $form,
        ]);
    }

    private function displayErrors(FormInterface $form): void
    {
        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
    }
}
