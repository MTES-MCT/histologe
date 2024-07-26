<?php

namespace App\Controller\Back;

use App\Entity\AutoAffectationRule;
use App\Entity\Partner;
use App\Form\AutoAffectationRuleType;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/auto-affectation')]
class AutoAffectationRuleController extends AbstractController
{
    #[Route('/', name: 'back_auto_affectation_rule_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        TerritoryRepository $territoryRepository,
        AutoAffectationRuleRepository $autoAffectationRuleRepository
    ): Response {
        $page = $request->get('page') ?? 1;

        $currentTerritory = $territoryRepository->find((int) $request->get('territory'));

        $paginatedAutoAffectationRule = $autoAffectationRuleRepository->getAutoAffectationRules(
            territory: $currentTerritory,
            page: (int) $page
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));

            return $this->redirect($this->generateUrl('back_auto_affectation_rule_index', [
                'page' => 1,
                'territory' => $currentTerritory?->getId(),
            ]));
        }

        $totalAutoAffectationRule = \count($paginatedAutoAffectationRule);

        return $this->render('back/auto-affectation-rule/index.html.twig', [
            'currentTerritory' => $currentTerritory,
            'territories' => $territoryRepository->findAllList(),
            'autoaffectationrules' => $paginatedAutoAffectationRule,
            'total' => $totalAutoAffectationRule,
            'page' => $page,
            'pages' => (int) ceil($totalAutoAffectationRule / Partner::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/supprimerregle', name: 'back_auto_affectation_rule_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAutoAffectationRule(
        Request $request,
        AutoAffectationRuleRepository $autoAffectationRuleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $ruleId = $request->request->get('autoaffectationrule_id');
        if (!$this->isCsrfTokenValid('autoaffectationrule_delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide, merci d\'actualiser la page et réessayer.');

            return $this->redirectToRoute('back_auto_affectation_rule_index', [], Response::HTTP_SEE_OTHER);
        }
        /** @var AutoAffectationRule $autoAffectationRule */
        $autoAffectationRule = $autoAffectationRuleRepository->findOneBy(['id' => $ruleId]);
        if (AutoAffectationRule::STATUS_ARCHIVED === $autoAffectationRule->getStatus()) {
            $this->addFlash('error', 'Cette règle est déjà archivée.');
        } else {
            $autoAffectationRule->setStatus(AutoAffectationRule::STATUS_ARCHIVED);
            $entityManager->flush();
            $this->addFlash('success', 'La règle a bien été archivée.');
        }

        return $this->redirectToRoute(
            'back_auto_affectation_rule_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/{id}/reactiverregle', name: 'back_auto_affectation_rule_reactive', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reactiveAutoAffectationRule(
        Request $request,
        AutoAffectationRule $autoAffectationRule,
        EntityManagerInterface $entityManager
    ): Response {
        if (AutoAffectationRule::STATUS_ACTIVE === $autoAffectationRule->getStatus()) {
            $this->addFlash('error', 'Cette règle est déjà active.');
        } else {
            $autoAffectationRule->setStatus(AutoAffectationRule::STATUS_ACTIVE);
            $entityManager->flush();
            $this->addFlash('success', 'La règle a bien été réactivée.');
        }

        return $this->redirectToRoute(
            'back_auto_affectation_rule_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/{id}/editer', name: 'back_auto_affectation_rule_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
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
            $this->addFlash('success', 'La règle a bien été modifiée.');

            return $this->redirectToRoute('back_auto_affectation_rule_index', []);
        }

        $this->displayErrors($form);

        return $this->render('back/auto-affectation-rule/edit.html.twig', [
            'autoAffectationRule' => $autoAffectationRule,
            'form' => $form,
            'create' => false,
        ]);
    }

    #[Route('/ajout', name: 'back_auto_affectation_rule_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
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
            $this->addFlash('success', 'La règle a bien été créée.');

            return $this->redirectToRoute('back_auto_affectation_rule_index', [], Response::HTTP_SEE_OTHER);
        }

        $this->displayErrors($form);

        return $this->render('back/auto-affectation-rule/edit.html.twig', [
            'autoAffectationRule' => $autoAffectationRule,
            'form' => $form,
            'create' => true,
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
