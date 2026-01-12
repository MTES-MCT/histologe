<?php

namespace App\Controller\Back;

use App\Entity\AutoAffectationRule;
use App\Form\AutoAffectationRuleType;
use App\Form\SearchAutoAffectationRuleType;
use App\Repository\AutoAffectationRuleRepository;
use App\Service\FormHelper;
use App\Service\ListFilters\SearchAutoAffectationRule;
use App\Service\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/auto-affectation')]
#[IsGranted('ROLE_ADMIN')]
class AutoAffectationRuleController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
        private readonly AutoAffectationRuleRepository $autoAffectationRuleRepository,
    ) {
    }

    /**
     * @return array{FormInterface, SearchAutoAffectationRule, Paginator<AutoAffectationRule>}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        $searchAutoAffectationRule = new SearchAutoAffectationRule();
        $form = $this->createForm(SearchAutoAffectationRuleType::class, $searchAutoAffectationRule);
        FormHelper::handleFormSubmitFromRequestOrSearchParams($form, $request, $fromSearchParams);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchAutoAffectationRule = new SearchAutoAffectationRule();
        }
        /** @var Paginator<AutoAffectationRule> $paginatedAutoAffectationRule */
        $paginatedAutoAffectationRule = $this->autoAffectationRuleRepository->findFilteredPaginated($searchAutoAffectationRule, $this->maxListPagination);

        return [$form, $searchAutoAffectationRule, $paginatedAutoAffectationRule];
    }

    private function getHtmlTargetContentsForAutoAffectationList(Request $request): array
    {
        [, $searchAutoAffectationRule, $paginatedAutoAffectationRule] = $this->handleSearch($request, true);

        return [
            [
                'target' => '#title-and-table-list-results',
                'content' => $this->renderView('back/auto-affectation-rule/_title-and-table-list-results.html.twig', [
                    'searchAutoAffectationRule' => $searchAutoAffectationRule,
                    'autoAffectationRules' => $paginatedAutoAffectationRule,
                    'pages' => (int) ceil($paginatedAutoAffectationRule->count() / $this->maxListPagination),
                ]),
            ],
        ];
    }

    #[Route('/', name: 'back_auto_affectation_rule_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        [$form, $searchAutoAffectationRule, $paginatedAutoAffectationRule] = $this->handleSearch($request);

        return $this->render('back/auto-affectation-rule/index.html.twig', [
            'form' => $form,
            'searchAutoAffectationRule' => $searchAutoAffectationRule,
            'autoAffectationRules' => $paginatedAutoAffectationRule,
            'pages' => (int) ceil($paginatedAutoAffectationRule->count() / $this->maxListPagination),
        ]);
    }

    #[Route('/supprimerregle', name: 'back_auto_affectation_rule_delete', methods: ['POST'])]
    public function deleteAutoAffectationRule(
        Request $request,
        AutoAffectationRuleRepository $autoAffectationRuleRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $ruleId = $request->request->get('autoaffectationrule_id');
        $flashMessages = [];
        if (!$this->isCsrfTokenValid('autoaffectationrule_delete', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
        }
        /** @var AutoAffectationRule $autoAffectationRule */
        $autoAffectationRule = $autoAffectationRuleRepository->findOneBy(['id' => $ruleId]);
        if (AutoAffectationRule::STATUS_ARCHIVED === $autoAffectationRule->getStatus()) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Cette règle est déjà archivée.'];
        } else {
            $autoAffectationRule->setStatus(AutoAffectationRule::STATUS_ARCHIVED);
            $entityManager->flush();
            $flashMessages[] = ['type' => 'success', 'title' => 'Succès', 'message' => 'La règle a bien été archivée.'];
        }
        $htmlTargetContents = $this->getHtmlTargetContentsForAutoAffectationList($request);

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{id}/reactiverregle', name: 'back_auto_affectation_rule_reactive', methods: ['POST'])]
    public function reactiveAutoAffectationRule(
        AutoAffectationRule $autoAffectationRule,
        EntityManagerInterface $entityManager,
        Request $request,
    ): JsonResponse {
        if (AutoAffectationRule::STATUS_ACTIVE === $autoAffectationRule->getStatus()) {
            $flashMessage = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Cette règle est déjà active.'];

            return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage]]);
        }
        $autoAffectationRule->setStatus(AutoAffectationRule::STATUS_ACTIVE);
        $entityManager->flush();
        $flashMessage = ['type' => 'success', 'title' => 'Règle réactivée', 'message' => 'La règle a bien été réactivée.'];
        $htmlTargetContents = $this->getHtmlTargetContentsForAutoAffectationList($request);

        return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage], 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{id}/editer', name: 'back_auto_affectation_rule_edit', methods: ['GET', 'POST'])]
    public function editAutoAffectationRule(
        Request $request,
        AutoAffectationRule $autoAffectationRule,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(AutoAffectationRuleType::class, $autoAffectationRule, [
            'create' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', ['title' => 'Modifications enregistrées', 'message' => 'La règle a bien été modifiée.']);

            return $this->redirectToRoute('back_auto_affectation_rule_index', []);
        }

        return $this->render('back/auto-affectation-rule/edit.html.twig', [
            'autoAffectationRule' => $autoAffectationRule,
            'form' => $form,
            'create' => false,
        ]);
    }

    #[Route('/ajout', name: 'back_auto_affectation_rule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $autoAffectationRule = new AutoAffectationRule();
        $form = $this->createForm(AutoAffectationRuleType::class, $autoAffectationRule, [
            'create' => true,
            'route' => 'back_auto_affectation_rule_new',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($autoAffectationRule);
            $entityManager->flush();
            $this->addFlash('success', ['title' => 'Nouvelle auto-affectation',
                'message' => 'La règle a bien été créée.',
            ]);

            return $this->redirectToRoute('back_auto_affectation_rule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/auto-affectation-rule/edit.html.twig', [
            'autoAffectationRule' => $autoAffectationRule,
            'form' => $form,
            'create' => true,
        ]);
    }
}
