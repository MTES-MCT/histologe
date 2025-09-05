<?php

namespace App\Tests\Unit\Service\Security;

use App\Entity\Partner;
use App\Entity\User;
use App\Service\Security\UserApiPermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserApiPermissionServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserApiPermissionService $userApiPermissionService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->userApiPermissionService = new UserApiPermissionService();
    }

    public function testGetUniquePartner(): void
    {
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', '"ROLE_API_USER"')
            ->getQuery()
            ->getResult();

        $this->assertCount(6, $users);
        foreach ($users as $user) {
            $uniquePartner = $this->userApiPermissionService->getUniquePartner($user);
            if ('api-01@signal-logement.fr' === $user->getEmail()) {
                $this->assertEquals('Partenaire 13-01', $uniquePartner->getNom());
            } elseif ('api-02@signal-logement.fr' === $user->getEmail()) {
                $this->assertEquals('Alès Agglomération', $uniquePartner->getNom());
            } else {
                $this->assertNull($uniquePartner);
            }
        }
    }

    public function testHasPermissionOnPartnerForPartnerIdPermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-01@signal-logement.fr']);
        $partnerOK = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 13-01']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 13-02']);

        $this->assertInstanceOf(Partner::class, $partnerOK);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerKO));
    }

    public function testHasPermissionOnPartnerForPartnerTypeAndTerritoryPermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-reunion-epci@signal-logement.fr']);
        $partnerOK1 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 974-01']);
        $partnerOK2 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 974-EPCI-02']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 974-AUTRE']);

        $this->assertInstanceOf(Partner::class, $partnerOK1);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK1));
        $this->assertInstanceOf(Partner::class, $partnerOK2);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK2));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerKO));
    }

    public function testHasPermissionOnPartnerForPartnerTypePermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-oilhi@signal-logement.fr']);
        $partnerOK1 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Mairie d\'Egriselles le Bocage']);
        $partnerOK2 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Mairie de Saint-Denis']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Alès Agglomération']);

        $this->assertInstanceOf(Partner::class, $partnerOK1);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK1));
        $this->assertInstanceOf(Partner::class, $partnerOK2);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK2));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerKO));
    }

    public function testHasPermissionOnPartnerForTerritoryPermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-full-63@signal-logement.fr']);
        $partnerOK1 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'ADIL 63']);
        $partnerOK2 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'CAF 63']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partner Habitat 44']);

        $this->assertInstanceOf(Partner::class, $partnerOK1);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK1));
        $this->assertInstanceOf(Partner::class, $partnerOK2);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK2));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerKO));
    }

    public function testHasPermissionOnMultiPermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-34-01@signal-logement.fr']);
        $partnerOK1 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Ville de Montpellier']);
        $partnerOK2 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'CA Lunel Agglomération']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Commune de Campagne']);

        $this->assertInstanceOf(Partner::class, $partnerOK1);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK1));
        $this->assertInstanceOf(Partner::class, $partnerOK2);
        $this->assertTrue($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerOK2));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->userApiPermissionService->hasPermissionOnPartner($user, $partnerKO));
    }
}
