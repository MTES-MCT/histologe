<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\Territory;
use App\Entity\User;
use App\Form\SearchDashboardAverifierType;
use App\Service\ListFilters\SearchDashboardAverifier;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

// use Symfony\Component\Security\Core\User\User;

class SearchDashboardAverifierTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    public function testFormContainsExpectedFieldsForSimpleUser(): void
    {
        $user = new User();
        $user->setNom('admin');
        $user->setPassword('pass');
        $user->setRoles(['ROLE_ADMIN_PARTNER']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get(TokenStorageInterface::class)->setToken($token);
        $form = $this->formFactory->create(SearchDashboardAverifierType::class, new SearchDashboardAverifier($user));

        $this->assertTrue($form->has('queryCommune'));
        $this->assertTrue($form->has('territoireId'));
        $this->assertTrue($form->has('mesDossiersAverifier'));
        $this->assertTrue($form->has('mesDossiersMessagesUsagers'));
        $this->assertTrue($form->has('mesDossiersActiviteRecente'));

        // pas de partenaires pour un user simple
        $this->assertFalse($form->has('partners'));
    }

    public function testFormContainsPartnersFieldForAdmin(): void
    {
        // 1. Simuler un utilisateur ROLE_SUPER_ADMIN
        $user = new User();
        $user->setNom('admin');
        $user->setPassword('pass');
        $user->setRoles(['ROLE_ADMIN_TERRITORY']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get(TokenStorageInterface::class)->setToken($token);

        // 2. Créer un Territory factice avec un id
        $territory = new Territory();
        $reflection = new \ReflectionClass($territory);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($territory, 42);

        // 3. Créer le formulaire
        $form = $this->formFactory->create(SearchDashboardAverifierType::class, new SearchDashboardAverifier($user), [
            'territory' => $territory,
        ]);

        // 4. Vérifier que le champ partners est bien présent
        $this->assertTrue($form->has('partners'));
    }
}
