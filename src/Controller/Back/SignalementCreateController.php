<?php

namespace App\Controller\Back;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Form\SearchDraftType;
use App\Form\SignalementDraftAddressType;
use App\Form\SignalementDraftCoordonneesType;
use App\Form\SignalementDraftDesordresType;
use App\Form\SignalementDraftLogementType;
use App\Form\SignalementDraftSituationType;
use App\Manager\SignalementManager;
use App\Repository\FileRepository;
use App\Repository\SignalementRepository;
use App\Service\ListFilters\SearchDraft;
use App\Service\Signalement\SignalementBoManager;
use App\Service\Signalement\SignalementDesordresProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/bo/signalement')]
class SignalementCreateController extends AbstractController
{
    public function __construct(
        private readonly SignalementBoManager $signalementBoManager,
        private readonly SignalementManager $signalementManager,
        #[Autowire(env: 'FEATURE_BO_SIGNALEMENT_CREATE')]
        bool $featureSignalementCreate,
    ) {
        if (!$featureSignalementCreate) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/brouillons', name: 'back_signalement_drafts', methods: ['GET'])]
    public function showDrafts(
        Request $request,
        SignalementRepository $signalementRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchDraft = new SearchDraft($user);
        $form = $this->createForm(SearchDraftType::class, $searchDraft);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchDraft = new SearchDraft($user);
        }
        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $paginatedDrafts = $signalementRepository->findFilteredPaginatedDrafts($searchDraft, $maxListPagination);

        return $this->render('back/signalement_drafts/index.html.twig', [
            'form' => $form,
            'searchDraft' => $searchDraft,
            'drafts' => $paginatedDrafts,
            'pages' => (int) ceil($paginatedDrafts->count() / $maxListPagination),
        ]);
    }

    #[Route('/brouillon/supprimer', name: 'back_signalement_delete_draft', methods: ['POST'])]
    public function deleteDraftSignalement(
        Request $request,
        SignalementManager $signalementManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $draftId = $request->request->get('draft_id');
        /** @var Signalement $signalement */
        $signalement = $signalementManager->find($draftId);

        $this->denyAccessUnlessGranted('SIGN_DELETE_DRAFT', $signalement);

        if (
            $signalement
            && $this->isCsrfTokenValid('draft_delete', $request->request->get('_token'))
        ) {
            $signalement->setStatut(SignalementStatus::DRAFT_ARCHIVED);
            $entityManager->flush();
            $this->addFlash('success', 'Le brouillon a bien été supprimé !');

            return $this->redirectToRoute('back_signalement_drafts', [], Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

        return $this->redirectToRoute('back_signalement_drafts', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/brouillon/creer', name: 'back_signalement_create', methods: ['GET'])]
    public function createSignalement(
    ): Response {
        $signalement = new Signalement();
        $formAddress = $this->createForm(SignalementDraftAddressType::class, $signalement, ['action' => $this->generateUrl('back_signalement_draft_form_address')]);

        return $this->render('back/signalement_create/index.html.twig', [
            'formAddress' => $formAddress,
            'signalement' => $signalement,
        ]);
    }

    #[Route('/brouillon/editer/{uuid:signalement}', name: 'back_signalement_edit_draft', methods: ['GET'])]
    public function editSignalement(
        Signalement $signalement,
        SignalementDesordresProcessor $signalementDesordresProcessor,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT_DRAFT', $signalement);
        $formAddress = $this->createForm(SignalementDraftAddressType::class, $signalement, [
            'action' => $this->generateUrl('back_signalement_draft_form_address_edit', ['uuid' => $signalement->getUuid()]),
        ]);
        $formLogement = $this->createForm(SignalementDraftLogementType::class, $signalement, [
            'action' => $this->generateUrl('back_signalement_draft_form_logement_edit', ['uuid' => $signalement->getUuid()]),
        ]);
        $formSituation = $this->createForm(SignalementDraftSituationType::class, $signalement, [
            'action' => $this->generateUrl('back_signalement_draft_form_situation_edit', ['uuid' => $signalement->getUuid()]),
        ]);
        $formCoordonnees = $this->createForm(SignalementDraftCoordonneesType::class, $signalement, [
            'action' => $this->generateUrl('back_signalement_draft_form_coordonnees_edit', ['uuid' => $signalement->getUuid()]),
        ]);
        $formDesordres = $this->createForm(SignalementDraftDesordresType::class, $signalement, [
            'action' => $this->generateUrl('back_signalement_draft_form_desordres_edit', ['uuid' => $signalement->getUuid()]),
        ]);

        $criteresByZone = $signalementDesordresProcessor->processDesordresByZone($signalement);

        return $this->render('back/signalement_create/index.html.twig', [
            'criteresByZone' => $criteresByZone,
            'formAddress' => $formAddress,
            'formLogement' => $formLogement,
            'formSituation' => $formSituation,
            'formCoordonnees' => $formCoordonnees,
            'formDesordres' => $formDesordres,
            'signalement' => $signalement,
        ]);
    }

    #[Route('/brouillon/{uuid:signalement}/liste-fichiers', name: 'back_signalement_create_file_list', methods: ['GET'])]
    public function getSignalementFileList(
        Signalement $signalement,
        FileRepository $fileRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_EDIT_DRAFT', $signalement);

        $files = $fileRepository->findBy(['signalement' => $signalement]);

        $jsonResult = [];
        foreach ($files as $file) {
            $jsonResult[] = [
                'id' => $file->getId(),
                'filename' => $file->getFilename(),
                'type' => $file->getDocumentType()->label(),
            ];
        }

        return $this->json($jsonResult);
    }

    #[Route('/bo-form-address/{uuid:signalement}', name: 'back_signalement_draft_form_address_edit', methods: ['POST'])]
    public function editFormAddress(
        Signalement $signalement,
        Request $request,
        SignalementRepository $signalementRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT_DRAFT', $signalement);

        return $this->submitFormAddressHandler($signalement, $request, $signalementRepository, $entityManager);
    }

    #[Route('/bo-form-address', name: 'back_signalement_draft_form_address', methods: ['POST'])]
    public function createFormAddress(
        Request $request,
        SignalementRepository $signalementRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $signalement = new Signalement();

        return $this->submitFormAddressHandler($signalement, $request, $signalementRepository, $entityManager);
    }

    private function submitFormAddressHandler(
        Signalement $signalement,
        Request $request,
        SignalementRepository $signalementRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $entityManager->beginTransaction();
        $isCreation = empty($signalement->getId());
        $action = $isCreation ? $this->generateUrl('back_signalement_draft_form_address') : $this->generateUrl('back_signalement_draft_form_address_edit', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(SignalementDraftAddressType::class, $signalement, ['action' => $action]);
        $form->handleRequest($request);
        $hasDuplicates = false;
        $duplicateContent = '';
        $linkDuplicates = '';
        $duplicates = [];
        if ($form->isSubmitted() && $form->isValid() && $this->signalementBoManager->formAddressManager($form, $signalement)) {
            if ($form->get('forceSave')->isEmpty() && $duplicates = $signalementRepository->findOnSameAddress($signalement)) {
                $hasDuplicates = true;
                $duplicateContent = $this->renderView('back/signalement_create/_modal_duplicate_content.html.twig', ['duplicates' => $duplicates]);
                $linkDuplicates = $this->generateUrl('back_signalements_index', [
                    'searchTerms' => $signalement->getAdresseOccupant(),
                    'communes[]' => $signalement->getCpOccupant(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            } else {
                $this->signalementManager->save($signalement);
                $entityManager->commit();
                if ($form->get('draft')->isClicked()) { // @phpstan-ignore-line
                    $this->addFlash('success', 'Le brouillon est bien enregistré, n\'oubliez pas de le terminer !');
                    $url = $this->generateUrl('back_signalement_drafts', [], UrlGeneratorInterface::ABSOLUTE_URL);
                } else {
                    $url = $isCreation ? $this->generateUrl('back_signalement_edit_draft', ['uuid' => $signalement->getUuid(), '_fragment' => 'logement'], UrlGeneratorInterface::ABSOLUTE_URL) : '';
                }

                $tabContent = $this->renderView('back/signalement_create/tabs/tab-adresse.html.twig', ['form' => $form]);

                return $this->json(['redirect' => true, 'url' => $url, 'tabContent' => $tabContent]);
            }
        }

        $tabContent = $this->renderView('back/signalement_create/tabs/tab-adresse.html.twig', ['form' => $form]);

        return $this->json(['tabContent' => $tabContent, 'hasDuplicates' => $hasDuplicates, 'duplicateContent' => $duplicateContent, 'linkDuplicates' => $linkDuplicates]);
    }

    #[Route('/bo-form-logement/{uuid:signalement}', name: 'back_signalement_draft_form_logement_edit', methods: ['POST'])]
    public function editFormLogement(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT_DRAFT', $signalement);

        $entityManager->beginTransaction();
        $action = $this->generateUrl('back_signalement_draft_form_logement_edit', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(SignalementDraftLogementType::class, $signalement, ['action' => $action]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->signalementBoManager->formLogementManager($form, $signalement)) {
            $this->signalementManager->save($signalement);
            $entityManager->commit();
            if ($form->get('draft')->isClicked()) { // @phpstan-ignore-line
                $this->addFlash('success', 'Le brouillon est bien enregistré, n\'oubliez pas de le terminer !');
                $url = $this->generateUrl('back_signalement_drafts', [], UrlGeneratorInterface::ABSOLUTE_URL);
            } else {
                $url = '';
            }

            $tabContent = $this->renderView('back/signalement_create/tabs/tab-logement.html.twig', ['formLogement' => $form]);

            return $this->json(['redirect' => true, 'tabContent' => $tabContent, 'url' => $url]);
        }

        $tabContent = $this->renderView('back/signalement_create/tabs/tab-logement.html.twig', ['formLogement' => $form]);

        return $this->json(['tabContent' => $tabContent]);
    }

    #[Route('/bo-form-situation/{uuid:signalement}', name: 'back_signalement_draft_form_situation_edit', methods: ['POST'])]
    public function editFormSituation(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT_DRAFT', $signalement);

        $entityManager->beginTransaction();
        $action = $this->generateUrl('back_signalement_draft_form_situation_edit', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(SignalementDraftSituationType::class, $signalement, ['action' => $action]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->signalementBoManager->formSituationManager($form, $signalement)) {
            $this->signalementManager->save($signalement);
            $entityManager->commit();
            if ($form->get('draft')->isClicked()) { // @phpstan-ignore-line
                $this->addFlash('success', 'Le brouillon est bien enregistré, n\'oubliez pas de le terminer !');
                $url = $this->generateUrl('back_signalement_drafts', [], UrlGeneratorInterface::ABSOLUTE_URL);
            } else {
                $url = '';
            }

            $tabContent = $this->renderView('back/signalement_create/tabs/tab-situation.html.twig', ['formSituation' => $form, 'signalement' => $signalement]);

            return $this->json(['redirect' => true, 'tabContent' => $tabContent, 'url' => $url]);
        }

        $tabContent = $this->renderView('back/signalement_create/tabs/tab-situation.html.twig', ['formSituation' => $form, 'signalement' => $signalement]);

        return $this->json(['tabContent' => $tabContent]);
    }

    #[Route('/bo-form-coordonnees/{uuid:signalement}', name: 'back_signalement_draft_form_coordonnees_edit', methods: ['POST'])]
    public function editFormCoordonnees(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT_DRAFT', $signalement);

        $entityManager->beginTransaction();
        $action = $this->generateUrl('back_signalement_draft_form_coordonnees_edit', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(SignalementDraftCoordonneesType::class, $signalement, ['action' => $action]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->signalementBoManager->formCoordonneesManager($form, $signalement)) {
            $this->signalementManager->save($signalement);
            $entityManager->commit();
            if ($form->get('draft')->isClicked()) { // @phpstan-ignore-line
                $this->addFlash('success', 'Le brouillon est bien enregistré, n\'oubliez pas de le terminer !');
                $url = $this->generateUrl('back_signalement_drafts', [], UrlGeneratorInterface::ABSOLUTE_URL);
            } else {
                $url = '';
            }

            $tabContent = $this->renderView('back/signalement_create/tabs/tab-coordonnees.html.twig', ['formCoordonnees' => $form, 'signalement' => $signalement]);

            return $this->json(['redirect' => true, 'tabContent' => $tabContent, 'url' => $url]);
        }

        $tabContent = $this->renderView('back/signalement_create/tabs/tab-coordonnees.html.twig', ['formCoordonnees' => $form, 'signalement' => $signalement]);

        return $this->json(['tabContent' => $tabContent]);
    }
    
    #[Route('/bo-form-desordres/{uuid:signalement}', name: 'back_signalement_draft_form_desordres_edit', methods: ['POST'])]
    public function editFormDesordres(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        SignalementDesordresProcessor $signalementDesordresProcessor,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT_DRAFT', $signalement);

        $entityManager->beginTransaction();
        $action = $this->generateUrl('back_signalement_draft_form_desordres_edit', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(SignalementDraftDesordresType::class, $signalement, ['action' => $action]);
        $form->handleRequest($request);
        $criteresByZone = $signalementDesordresProcessor->processDesordresByZone($signalement);
        if ($form->isSubmitted() && $form->isValid() && $this->signalementBoManager->formDesordresManager($form, $signalement)) {
            $this->signalementManager->save($signalement);
            $entityManager->commit();
            if ($form->get('draft')->isClicked()) { // @phpstan-ignore-line
                $this->addFlash('success', 'Le brouillon est bien enregistré, n\'oubliez pas de le terminer !');
                $url = $this->generateUrl('back_signalement_drafts', [], UrlGeneratorInterface::ABSOLUTE_URL);
            } else {
                $url = '';
            }

            $tabContent = $this->renderView('back/signalement_create/tabs/tab-desordres.html.twig', [
                'formDesordres' => $form,
                'signalement' => $signalement,
                'criteresByZone' => $criteresByZone,
            ]);

            return $this->json(['redirect' => true, 'url' => $url, 'tabContent' => $tabContent]);
        }

        $tabContent = $this->renderView('back/signalement_create/tabs/tab-desordres.html.twig', [
            'formDesordres' => $form,
            'signalement' => $signalement,
            'criteresByZone' => $criteresByZone,
        ]);

        return $this->json(['tabContent' => $tabContent]);
    }
}
