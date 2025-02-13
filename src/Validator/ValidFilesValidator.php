<?php

namespace App\Validator;

use App\Entity\File;
use App\Repository\SignalementRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidFilesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SignalementRepository $signalementRepository,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidFiles) {
            throw new UnexpectedTypeException($constraint, ValidFiles::class);
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $signalement = $this->signalementRepository->findOneBy([
            'uuid' => $this->requestStack->getCurrentRequest()->get('signalement')]
        );

        if (null === $signalement) {
            return;
        }

        $uuidFiles = array_map(fn (File $file) => $file->getUuid(), $signalement->getFiles()->toArray());

        foreach ($value as $uuid) {
            if (!is_string($uuid) || !UuidV4::isValid($uuid)) {
                $this->context->buildViolation('Le fichier avec l\'UUID "{{ uuid }}" n\'est pas un UUID valide.')
                    ->setParameter('{{ uuid }}', $uuid)
                    ->addViolation();
                continue;
            }
            if (!in_array($uuid, $uuidFiles)) {
                $this->context->buildViolation('Le fichier avec l\'UUID "{{ uuid }}" n\'appartient pas au signalement.')
                    ->setParameter('{{ uuid }}', $uuid)
                    ->addViolation();
            }
        }
    }
}
