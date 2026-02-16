<?php

namespace App\Validator;

use App\Entity\UserSearchFilter;
use App\Repository\UserSearchFilterRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserSearchFilterParamsValidator extends ConstraintValidator
{
    public function __construct(private UserSearchFilterRepository $repo)
    {
    }

    /**
     * @param UserSearchFilterParams $constraint
     */
    public function validate(mixed $entity, Constraint $constraint): void
    {
        if (!$entity instanceof UserSearchFilter) {
            return;
        }

        $user = $entity->getUser();
        $params = $entity->getParams();

        if (!$user || !$params) {
            return;
        }

        $count = $this->repo->countForUser($user);
        if (!$entity->getId() && $count >= 5) {
            $this->context
                ->buildViolation('Vous avez atteint la limite de 5 recherches enregistrées.')
                ->addViolation();
        }

        $normalizedNew = $this->normalizeParams($params);

        $existingSearches = $this->repo->findBy(['user' => $user]);

        foreach ($existingSearches as $existing) {
            if ($existing->getId() === $entity->getId()) {
                continue;
            }

            $existingParams = $existing->getParams();
            if (!is_array($existingParams)) {
                continue;
            }

            $normalizedExisting = $this->normalizeParams($existingParams);

            if ($normalizedExisting === $normalizedNew) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->addViolation();

                return;
            }
        }
    }

    private function normalizeParams(array $params): array
    {
        ksort($params);

        return array_map(
            fn ($value) => is_array($value) ? $this->normalizeParams($value) : (string) $value,
            $params
        );
    }
}
