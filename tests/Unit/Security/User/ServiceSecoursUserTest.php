<?php

namespace App\Tests\Unit\Security\User;

use App\Security\User\ServiceSecoursUser;
use PHPUnit\Framework\TestCase;

class ServiceSecoursUserTest extends TestCase
{
    public function testGetters(): void
    {
        $user = new ServiceSecoursUser(
            userIdentifier: 'agent@pompiers.fr:route-uuid-123:MAT-123',
            email: 'agent@pompiers.fr',
            serviceSecoursRouteUuid: 'route-uuid-123',
            matricule: 'MAT-123',
        );

        $this->assertSame('agent@pompiers.fr:route-uuid-123:MAT-123', $user->getUserIdentifier());
        $this->assertSame('agent@pompiers.fr', $user->getEmail());
        $this->assertSame('route-uuid-123', $user->getServiceSecoursRouteUuid());
        $this->assertSame('MAT-123', $user->getMatricule());
        $this->assertSame(['ROLE_SERVICE_SECOURS'], $user->getRoles());

        $user->eraseCredentials();
        $this->assertSame('agent@pompiers.fr', $user->getEmail());
    }
}
