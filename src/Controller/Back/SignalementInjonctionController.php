<?php

namespace App\Controller\Back;

use App\Entity\Enum\MotifClotureUsager;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\User;
use App\Form\AdminCancelInjonctionProcedureType;
use App\Form\SearchSignalementInjonctionType;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Security\Voter\InjonctionBailleurVoter;
use App\Security\Voter\SignalementVoter;
use App\Service\InjonctionBailleur\CourrierBailleurGenerator;
use App\Service\ListFilters\SearchSignalementInjonction;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Utils\FormHelper;
use App\Utils\HtmlCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/bo/signalement-injonction')]
class SignalementInjonctionController extends AbstractController
{
    #[Route('/', name: 'back_injonction_signalement_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted(InjonctionBailleurVoter::INJONCTION_BAILLEUR_SEE);

        $searchSignalementInjonction = new SearchSignalementInjonction($user);
        $form = $this->createForm(SearchSignalementInjonctionType::class, $searchSignalementInjonction);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchSignalementInjonction = new SearchSignalementInjonction($user);
        }
        $userPartners = (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) ? $user->getPartners() : null;
        $paginatedSignalementInjonction = $signalementRepository->findInjonctionFilteredPaginated($searchSignalementInjonction, $maxListPagination, $userPartners);

        return $this->render('back/signalement-injonction/index.html.twig', [
            'form' => $form,
            'searchSignalement' => $searchSignalementInjonction,
            'signalements' => $paginatedSignalementInjonction,
            'pages' => (int) ceil($paginatedSignalementInjonction->count() / $maxListPagination),
        ]);
    }

    #[Route('/{uuid:signalement}/courrier-bailleur', name: 'back_injonction_signalement_courrier_bailleur', methods: ['GET'])]
    public function courrierBailleur(
        Signalement $signalement,
        CourrierBailleurGenerator $courrierBailleurGenerator,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_VIEW_INJONCTION_COURRIER, $signalement);

        $pdfContent = $courrierBailleurGenerator->generate($signalement);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="courrier-bailleur.pdf"');

        return $response;
    }

    #[Route('/{uuid:signalement}/courrier-bailleur-fermeture', name: 'back_injonction_signalement_courrier_bailleur_injonction_closed', methods: ['GET'])]
    public function courrierBailleurFermeture(
        Signalement $signalement,
        CourrierBailleurGenerator $courrierBailleurGenerator,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_VIEW_INJONCTION_COURRIER, $signalement);

        $pdfContent = $courrierBailleurGenerator->generateInjonctionClosed($signalement);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="courrier-bailleur.pdf"');

        return $response;
    }

    #[Route('/{uuid:signalement}/admin-cancel-procedure', name: 'back_injonction_signalement_admin_cancel_procedure', methods: 'POST')]
    public function adminCancelInjonctionProcedure(
        Signalement $signalement,
        Request $request,
        SuiviManager $suiviManager,
        EntityManagerInterface $entityManager,
        SignalementManager $signalementManager,
        AffectationManager $affectationManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_INJONCTION_CLOSE, $signalement);

        $adminCancelInjonctionProcedureFormRoute = $this->generateUrl('back_injonction_signalement_admin_cancel_procedure', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(AdminCancelInjonctionProcedureType::class, options: ['action' => $adminCancelInjonctionProcedureFormRoute]);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->json(['code' => Response::HTTP_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
        }
        if (!$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

            return $this->json($response, $response['code']);
        }

        /** @var User $user */
        $user = $this->getUser();
        /** @var MotifClotureUsager $motif */
        $motif = $form->get('reason')->getData();
        $details = HtmlCleaner::cleanFrontEndEntry($form->get('details')->getData());

        $signalementManager->closeInjonction(
            signalement: $signalement,
            closedBy: $user,
            motifClotureUsager: $motif,
            motifCloture: $motif->mapMotifCloture(),
            description: $details,
        );
        $affectationManager->closeBySignalement(
            signalement: $signalement,
            motif: $motif->mapMotifCloture(),
            user: $user,
            partner: null
        );

        $description = \sprintf(SuiviDescriptionHelper::DESCRIPTION_MOTIF_CLOTURE_INJONCTION_ADMIN, $motif->labelForAdmin(), $details);
        $suiviManager->createSuivi(
            signalement: $signalement,
            description: $description,
            category: SuiviCategory::INJONCTION_BAILLEUR_CLOTURE_PAR_ADMIN,
            user: $user,
            isVisibleForUsager: true,
            isVisibleForBailleur: true,
        );

        $entityManager->flush();

        $this->addFlash('success', ['title' => 'Dossier fermé', 'message' => sprintf('Le dossier #%s a bien été fermé.', $signalement->getReference())]);

        $url = $this->generateUrl('back_injonction_signalement_index', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(['redirect' => true, 'url' => $url]);
    }
}
