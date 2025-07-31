<?php

namespace App\Tests\Unit\Form;

use App\Dto\AcceptAffectation;
use App\Entity\Affectation;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Form\AcceptAffectationType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class AcceptAffectationTypeTest extends KernelTestCase
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
        $dto = (new AcceptAffectation())->setAffectation($affectation);
        $form = $this->formFactory->create(AcceptAffectationType::class, $dto, [
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
}
