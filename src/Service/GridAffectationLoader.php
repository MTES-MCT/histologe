<?php

namespace App\Service;

use App\Entity\Territory;
use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\ManagerInterface;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Service\Parser\CsvParser;

class GridAffectationLoader
{
    private array $metadata = [
        'nb_users' => 0,
        'nb_partners' => 0,
    ];

    public function __construct(
        private CsvParser $csvParser,
        private PartnerFactory $partnerFactory,
        private PartnerManager $partnerManager,
        private UserFactory $userFactory,
        private UserManager $userManager,
        private ManagerInterface $manager
    ) {
    }

    public function load(array $data, Territory $territory)
    {
        $partner = null;
        foreach ($data as $lineNumber => $row) {
            if (0 === $lineNumber || $data[$lineNumber][0] !== $data[$lineNumber - 1][0]) {
                $partner = $this->partnerFactory->createInstanceFrom(
                    territory: $territory,
                    name: $row[0],
                    email: !empty($row[3]) ? $row[3] : null,
                    isCommune: !empty($row[1]) ? true : false,
                    insee: !empty($row[1]) ? [$row[2]] : [],
                );
                $this->partnerManager->save($partner, false);
                ++$this->metadata['nb_partners'];
            }

            $user = $this->userFactory->createInstanceFrom(
                roleLabel: $row[4],
                territory: $territory,
                partner: $partner,
                firstname: $row[5],
                lastname: $row[6],
                email: $row[7]
            );
            $this->userManager->save($user, false);
            ++$this->metadata['nb_users'];
        }

        $this->manager->flush();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
