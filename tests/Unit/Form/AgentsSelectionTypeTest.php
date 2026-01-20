<?php

namespace App\Tests\Unit\Form;

use App\Dto\AgentSelection;
use App\Entity\Affectation;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Form\AgentSelectionType;
use App\Repository\AffectationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;

class AgentsSelectionTypeTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->formFactory = static::getContainer()->get(FormFactoryInterface::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testAgentLabelsFromFixtures(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        /** @var AffectationRepository $affectationRepo */
        $affectationRepo = $em->getRepository(Affectation::class);

        $affectation = $affectationRepo->findOneBy(['statut' => 'NOUVEAU']);
        $dto = (new AgentSelection())->setSignalement($affectation->getSignalement());
        $form = $this->formFactory->create(AgentSelectionType::class, $dto, [
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
            /** @var string $roleRaw */
            $roleRaw = isset($match[1]) ? trim(strip_tags($match[1])) : '';
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

        $dto = (new AgentSelection())->setSignalement($affectation->getSignalement());
        $form = $this->formFactory->create(AgentSelectionType::class, $dto, [
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

        $dto = (new AgentSelection())->setSignalement($affectation->getSignalement());
        $form = $this->formFactory->create(AgentSelectionType::class, $dto);

        $config = $form->getConfig();
        $this->assertSame(AgentSelection::class, $config->getOption('data_class'));
        $this->assertNull($config->getOption('exclude_user'));
        $this->assertSame('Sélectionnez le(s) agent(s) à abonner au dossier', $config->getOption('label'));
    }

    public function testBlockPrefix(): void
    {
        $formType = new AgentSelectionType(
            static::getContainer()->get(UserRepository::class),
            static::getContainer()->get(Security::class)
        );
        $this->assertSame('agents_selection', $formType->getBlockPrefix());
    }
}
