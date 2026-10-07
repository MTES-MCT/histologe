<?php

namespace App\Controller\Back;

use App\Form\DocumentIAType;
use App\Service\Gouv\DocumentIA\DocumentIAService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/tools/document-ia')]
#[IsGranted('ROLE_ADMIN')]
class DocumentIAToolController extends AbstractController
{
    #[Route('/', name: 'back_tools_document_ia', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        DocumentIAService $documentIAService,
        #[Autowire(env: 'DOCUMENT_IA_ENABLE')]
        bool $documentIAEnable,
    ): Response {
        if (!$documentIAEnable) {
            return $this->render('back/tools/document-ia.html.twig');
        }

        $workflows = $documentIAService->listWorkflows();

        $documentIAForm = $this->createForm(DocumentIAType::class);
        $documentIAForm->handleRequest($request);
        $results = [];

        if ($workflows && $documentIAForm->isSubmitted()) {
            if ($documentIAForm->isValid()) {
                $workflowId = $documentIAForm->get('workflowId')->getData();
                $file = $documentIAForm->get('file')->getData();

                if ($workflowId && $file) {
                    $workflowFound = false;
                    foreach ($workflows as $workflow) {
                        if ($workflow['id'] === $workflowId) {
                            $workflowFound = true;
                            break;
                        }
                    }
                    if (!$workflowFound) {
                        $this->addFlash('error', 'L\'ID du workflow fourni n\'est pas valide.');
                    }

                    $fileExtensionValid = true;
                    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                    $fileExtension = strtolower($file->getClientOriginalExtension());
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        $this->addFlash('error', 'Le fichier doit être au format PDF, JPG ou PNG.');
                        $fileExtensionValid = false;
                    }

                    if ($workflowFound && $fileExtensionValid) {
                        try {
                            $results = $documentIAService->executeWorkflow($workflowId, $file);
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Erreur lors de l\'exécution du workflow : '.$e->getMessage());
                        }
                    }
                } else {
                    $this->addFlash('error', 'Veuillez fournir un ID de workflow et un fichier.');
                }
            } else {
                $this->addFlash('error', 'Le formulaire est invalide. Veuillez vérifier les champs.');
            }
        }

        return $this->render('back/tools/document-ia.html.twig', [
            'documentIAWorkflows' => $workflows,
            'documentIAForm' => $documentIAForm->createView(),
            'results' => $results,
        ]);
    }
}
