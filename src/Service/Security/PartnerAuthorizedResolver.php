<?php

namespace App\Service\Security;

use App\Entity\Partner;
use App\Entity\User;
use App\Repository\PartnerRepository;

readonly class PartnerAuthorizedResolver
{
    public function __construct(
        private PartnerRepository $partnerRepository,
    ) {
    }

    /**
     * @return Partner[]
     */
    public function resolveBy(User $user): array
    {
        $permissions = $user->getUserApiPermissions();
        $listPartner = [];
        foreach ($permissions as $permission) {
            if (null !== $permission->getPartner()) {
                if ($partner = $this->partnerRepository->findOneBy(['id' => $permission->getPartner()->getId(), 'isArchive' => false])) {
                    $listPartner[$partner->getId()] = $partner;
                }
                continue;
            }

            $territory = $permission->getTerritory();
            $partnerType = $permission->getPartnerType();

            if ($territory && $partnerType) {
                $partners = $this->partnerRepository->findBy(['territory' => $territory, 'type' => $partnerType, 'isArchive' => false]);
                foreach ($partners as $partner) {
                    $listPartner[$partner->getId()] = $partner;
                }
                continue;
            }

            if ($partnerType) {
                $partners = $this->partnerRepository->findBy(['type' => $partnerType, 'isArchive' => false]);
                foreach ($partners as $partner) {
                    $listPartner[$partner->getId()] = $partner;
                }
                continue;
            }

            if ($territory) {
                $partners = $this->partnerRepository->findBy(['territory' => $territory, 'isArchive' => false]);
                foreach ($partners as $partner) {
                    $listPartner[$partner->getId()] = $partner;
                }
            }
        }

        return array_values($listPartner);
    }

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
