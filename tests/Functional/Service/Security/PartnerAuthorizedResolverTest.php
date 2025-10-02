<?php

namespace App\Tests\Functional\Service\Security;

use App\Entity\Partner;
use App\Entity\User;
use App\EventListener\SecurityApiExceptionListener;
use App\Repository\PartnerRepository;
use App\Service\Security\PartnerAuthorizedResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PartnerAuthorizedResolverTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PartnerRepository $partnerRepository;
    private PartnerAuthorizedResolver $partnerAuthorizedResolver;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $this->partnerAuthorizedResolver = new PartnerAuthorizedResolver($this->partnerRepository);
    }

    public function testGetUniquePartner(): void
    {
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', '"ROLE_API_USER"')
            ->getQuery()
            ->getResult();

        $this->assertCount(7, $users);
        foreach ($users as $user) {
            $uniquePartner = $this->partnerAuthorizedResolver->getUniquePartner($user);
            if ('api-02@signal-logement.fr' === $user->getEmail()) {
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
        $partner2OK = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 13-02']);
        $partner3OK = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 13-03']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 13-04']);

        $this->assertInstanceOf(Partner::class, $partnerOK);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK));
        $this->assertInstanceOf(Partner::class, $partner2OK);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partner2OK));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerKO));
    }

    public function testHasPermissionOnPartnerForPartnerTypeAndTerritoryPermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-reunion-epci@signal-logement.fr']);
        $partnerOK1 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 974-01']);
        $partnerOK2 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 974-EPCI-02']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partenaire 974-AUTRE']);

        $this->assertInstanceOf(Partner::class, $partnerOK1);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK1));
        $this->assertInstanceOf(Partner::class, $partnerOK2);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK2));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerKO));
    }

    public function testHasPermissionOnPartnerForPartnerTypePermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-oilhi@signal-logement.fr']);
        $partnerOK1 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Mairie d\'Egriselles le Bocage']);
        $partnerOK2 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Mairie de Saint-Denis']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Alès Agglomération']);

        $this->assertInstanceOf(Partner::class, $partnerOK1);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK1));
        $this->assertInstanceOf(Partner::class, $partnerOK2);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK2));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerKO));
    }

    public function testHasPermissionOnPartnerForTerritoryPermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-full-63@signal-logement.fr']);
        $partnerOK1 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'ADIL 63']);
        $partnerOK2 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'CAF 63']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Partner Habitat 44']);

        $this->assertInstanceOf(Partner::class, $partnerOK1);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK1));
        $this->assertInstanceOf(Partner::class, $partnerOK2);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK2));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerKO));
    }

    public function testHasPermissionOnMultiPermission(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'api-34-01@signal-logement.fr']);
        $partnerOK1 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Ville de Montpellier']);
        $partnerOK2 = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'CA Lunel Agglomération']);
        $partnerKO = $this->entityManager->getRepository(Partner::class)->findOneBy(['nom' => 'Commune de Campagne']);

        $this->assertInstanceOf(Partner::class, $partnerOK1);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK1));
        $this->assertInstanceOf(Partner::class, $partnerOK2);
        $this->assertTrue($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerOK2));
        $this->assertInstanceOf(Partner::class, $partnerKO);
        $this->assertFalse($this->partnerAuthorizedResolver->hasPermissionOnPartner($user, $partnerKO));
    }

    /**
     * @dataProvider provideUsersWithApiRole
     */
    public function testResolveBy(string $email, int $countExpectedPartner): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        $resultPartners = $this->partnerAuthorizedResolver->resolveBy($user);

        $this->assertCount($countExpectedPartner, $resultPartners);
    }

    public function provideUsersWithApiRole(): \Generator
    {
        yield 'api-01@signal-logement.fr' => [
            'email' => 'api-01@signal-logement.fr',
            'countExpectedPartner' => 3,
        ];

        yield 'api-02@signal-logement.fr' => [
            'email' => 'api-02@signal-logement.fr',
            'countExpectedPartner' => 1,
        ];

        yield 'api-reunion-epci@signal-logement.fr' => [
            'email' => 'api-reunion-epci@signal-logement.fr',
            'countExpectedPartner' => 2,
        ];

        yield 'api-oilhi@signal-logement.fr' => [
            'email' => 'api-oilhi@signal-logement.fr',
            'countExpectedPartner' => 31,
        ];

        yield 'api-34-01@signal-logement.fr' => [
            'email' => 'api-34-01@signal-logement.fr',
            'countExpectedPartner' => 4,
        ];

        yield 'api-full-63@signal-logement.fr' => [
            'email' => 'api-full-63@signal-logement.fr',
            'countExpectedPartner' => 19,
        ];
    }

    public function testResolvePartnersThrowsAccessDeniedCauseNoPartnerPermissions(): void
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'api-03@signal-logement.fr']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(SecurityApiExceptionListener::ACCESS_DENIED_PARTNER);

        $this->partnerAuthorizedResolver->resolvePartners($user);
    }
}
