<?php

namespace App\Service\Security;

use App\Entity\Partner;
use App\Entity\User;

class UserApiPermissionService
{
    public function hasPermissionOnPartner(User $user, Partner $partner): bool
    {
        if ($partner->getIsArchive()) {
            return false;
        }

        foreach ($user->getUserApiPermissions() as $permission) {
            if ($permission->getPartner()) {
                if ($permission->getPartner()->getId() === $partner->getId()) {
                    return true;
                }
            } elseif ($permission->getPartnerType() && $permission->getTerritory()) {
                if ($permission->getPartnerType() === $partner->getType() && $permission->getTerritory()->getId() === $partner->getTerritory()->getId()) {
                    return true;
                }
            } elseif ($permission->getPartnerType()) {
                if ($permission->getPartnerType() === $partner->getType()) {
                    return true;
                }
            } elseif ($permission->getTerritory()) {
                if ($permission->getTerritory()->getId() === $partner->getTerritory()->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getUniquePartner(User $user): ?Partner
    {
        if (1 === $user->getUserApiPermissions()->count()) {
            return $user->getUserApiPermissions()->first()->getPartner();
        }

        return null;
    }
}
