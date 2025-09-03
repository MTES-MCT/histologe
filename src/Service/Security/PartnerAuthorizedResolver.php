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
                if ($partner = $this->partnerRepository->find($permission->getPartner()->getId())) {
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
}
