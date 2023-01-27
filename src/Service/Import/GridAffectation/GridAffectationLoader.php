<?php

namespace App\Service\Import\GridAffectation;

use App\Entity\Territory;
use App\Entity\User;
use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\ManagerInterface;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Service\Import\CsvParser;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        private ManagerInterface $manager,
        private ValidatorInterface $validator,
    ) {
    }

    public function load(array $data, Territory $territory): void
    {
        $partner = null;
        foreach ($data as $lineNumber => $row) {
            if (0 === $lineNumber || $data[$lineNumber][0] !== $data[$lineNumber - 1][0]) {
                $partner = $this->partnerFactory->createInstanceFrom(
                    territory: $territory,
                    name: $row[GridAffectationHeader::PARTNER_NAME_INSTITUTION],
                    email: !empty($row[GridAffectationHeader::PARTNER_EMAIL]) ? $row[GridAffectationHeader::PARTNER_EMAIL] : null,
                    isCommune: !empty($row[GridAffectationHeader::PARTNER_TYPE]) ? true : false,
                    insee: $row[GridAffectationHeader::PARTNER_CODE_INSEE]
                );
                $this->partnerManager->save($partner, false);
                ++$this->metadata['nb_partners'];
            }

            $roleLbal = $row[GridAffectationHeader::USER_ROLE];
            $email = $row[GridAffectationHeader::USER_EMAIL];
            if (!empty($roleLbal) && !empty($email)) {
                $user = $this->userFactory->createInstanceFrom(
                    roleLabel: $row[GridAffectationHeader::USER_ROLE],
                    territory: $territory,
                    partner: $partner,
                    firstname: $row[GridAffectationHeader::USER_FIRSTNAME],
                    lastname: $row[GridAffectationHeader::USER_LASTNAME],
                    email: $row[GridAffectationHeader::USER_EMAIL]
                );

                $this->throwException($user);

                $this->userManager->save($user, false);
                ++$this->metadata['nb_users'];
            }
        }

        $this->manager->flush();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function throwException(User $user): void
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($user);
        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                throw new \Exception(sprintf('%s (%s)', $error->getMessage(), $user->getEmail()));
            }
        }
    }
}
