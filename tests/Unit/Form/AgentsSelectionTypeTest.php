<?php

namespace App\Tests\Unit\Form;

use App\Dto\AgentsSelection;
use App\Entity\Affectation;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Form\AgentsSelectionType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class AgentsSelectionTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = static::getContainer()->get(FormFactoryInterface::class);
    }

    public function testAgentLabelsFromFixtures(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $affectationRepo = $em->getRepository(Affectation::class);

        $affectation = $affectationRepo->findOneBy(['statut' => 'NOUVEAU']);
        $dto = (new AgentsSelection())->setAffectation($affectation);
        $form = $this->formFactory->create(AgentsSelectionType::class, $dto, [
            'csrf_protection' => false,
        ]);
        $view = $form->createView();
        $choices = $view->children['agents']->vars['choices'];
        $rolesAllowed = array_keys(User::ROLES);
        foreach ($choices as $choice) {
            /** @var User $user */
            $user = $choice->data;
            $labelHtml = $choice->label;

            $this->assertNotEmpty($labelHtml);
            $this->assertStringContainsString('@', $labelHtml);

            preg_match('/<small[^>]*>(.*?)<\/small>/', $labelHtml, $match);
            $roleRaw = trim(strip_tags($match[1]));
            if (UserStatus::INACTIVE === $user->getStatut()) {
                $this->assertStringContainsString('fr-icon-warning-line', $labelHtml);
                $this->assertStringContainsString('Compte inactif', $roleRaw);
                $roleLabel = trim(explode('-', $roleRaw)[0]);
                $this->assertTrue(
                    in_array($roleLabel, $rolesAllowed, true),
                    sprintf('[INACTIF] Le rôle "%s" n’est pas dans User::ROLES. Label : %s', $roleLabel, $labelHtml)
                );
            } else {
                $this->assertStringNotContainsString('Compte inactif', $roleRaw);
                $this->assertStringNotContainsString('fr-icon-warning-line', $labelHtml);
                $this->assertTrue(
                    in_array($roleRaw, $rolesAllowed, true),
                    sprintf('[ACTIF] Le rôle "%s" n’est pas dans User::ROLES. Label : %s', $roleRaw, $labelHtml)
                );
            }
        }
    }

    public function testExcludeUserOptionRemovesUserFromChoices(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $affectation = $em->getRepository(Affectation::class)->findOneBy([]);

        $users = $affectation->getPartner()->getUsers();
        $excluded = $users->first(); // prend le premier pour le test

        $dto = (new AgentsSelection())->setAffectation($affectation);
        $form = $this->formFactory->create(AgentsSelectionType::class, $dto, [
            'csrf_protection' => false,
            'exclude_user' => $excluded,
        ]);
        $choices = array_map(fn ($c) => $c->data, $form->createView()->children['agents']->vars['choices']);

        $this->assertNotContains($excluded, $choices, 'L’utilisateur exclu ne doit pas apparaître dans les choix.');
    }

    public function testDefaultOptions(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $affectation = $em->getRepository(Affectation::class)->findOneBy([]);

        $users = $affectation->getPartner()->getUsers();
        $excluded = $users->first(); // prend le premier pour le test

        $dto = (new AgentsSelection())->setAffectation($affectation);
        $form = $this->formFactory->create(AgentsSelectionType::class, $dto);

        $config = $form->getConfig();
        $this->assertSame(AgentsSelection::class, $config->getOption('data_class'));
        $this->assertNull($config->getOption('exclude_user'));
        $this->assertSame('Sélectionnez le(s) agent(s) en charge du dossier', $config->getOption('label'));
    }

    public function testBlockPrefix(): void
    {
        $formType = new AgentsSelectionType();
        $this->assertSame('agents_selection', $formType->getBlockPrefix());
    }
}
