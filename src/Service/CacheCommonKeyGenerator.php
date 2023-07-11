<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class CacheCommonKeyGenerator
{
    public function __construct(private readonly Security $security)
    {
    }

    public function generate(): ?string
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $role = $user?->getRoles();

        return \is_array($role) ? array_shift($role).'-partnerId-'.$user?->getPartner()?->getId() : null;
    }
}
