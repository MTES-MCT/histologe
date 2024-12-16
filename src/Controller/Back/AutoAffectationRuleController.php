<?php

namespace App\Controller\Back;

use App\Entity\AutoAffectationRule;
use App\Form\AutoAffectationRuleType;
use App\Form\SearchAutoAffectationRuleType;
use App\Repository\AutoAffectationRuleRepository;
use App\Service\SearchAutoAffectationRule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/auto-affectation')]
class AutoAffectationRuleController extends AbstractController
{
    private const int MAX_LIST_PAGINATION = 50;

    #[Route('/', name: 'back_auto_affectation_rule_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        AutoAffectationRuleRepository $autoAffectationRuleRepository,
    ): Response {
        $searchAutoAffectationRule = new SearchAutoAffectationRule($this->getUser());
        $form = $this->createForm(SearchAutoAffectationRuleType::class, $searchAutoAffectationRule);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchAutoAffectationRule = new SearchAutoAffectationRule($this->getUser());
        }
        $paginatedAutoAffectationRule = $autoAffectationRuleRepository->findFilteredPaginated($searchAutoAffectationRule, self::MAX_LIST_PAGINATION);

        return $this->render('back/auto-affectation-rule/index.html.twig', [
            'form' => $form,
            'searchAutoAffectationRule' => $searchAutoAffectationRule,
            'autoAffectationRules' => $paginatedAutoAffectationRule,
            'pages' => (int) ceil($paginatedAutoAffectationRule->count() / self::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/supprimerregle', name: 'back_auto_affectation_rule_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAutoAffectationRule(
        Request $request,
        AutoAffectationRuleRepository $autoAffectationRuleRepository,
        EntityManagerInterface $entityManager,
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
        AutoAffectationRule $autoAffectationRule,
        EntityManagerInterface $entityManager,
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
