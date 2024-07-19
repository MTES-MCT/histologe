<?php

namespace App\Tests\Unit\Validator;

use App\Entity\Enum\ProfileDeclarant;
use App\Validator\ValidProfileDeclarant;
use App\Validator\ValidProfileDeclarantValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidProfileDeclarantValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValidProfileDeclarantValidator();
    }

    /**
     * @dataProvider provideProfil
     */
    public function testProfilsFormat(string $profil, bool $isProfilValid, ?string $message = null): void
    {
        $constraint = new ValidProfileDeclarant();
        $this->validator->validate($profil, $constraint);
        if ($isProfilValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($message)
                ->setParameter('{{ value }}', $profil)
                ->assertRaised();
        }
    }

    public function provideProfil(): \Generator
    {
        yield 'all' => ['all', true];
        yield 'tiers' => ['tiers', true];
        yield 'occupant' => ['occupant', true];
        yield ProfileDeclarant::BAILLEUR->value => [ProfileDeclarant::BAILLEUR->value, true];
        yield ProfileDeclarant::BAILLEUR_OCCUPANT->value => [ProfileDeclarant::BAILLEUR_OCCUPANT->value, true];
        yield ProfileDeclarant::LOCATAIRE->value => [ProfileDeclarant::LOCATAIRE->value, true];
        yield ProfileDeclarant::SERVICE_SECOURS->value => [ProfileDeclarant::SERVICE_SECOURS->value, true];
        yield ProfileDeclarant::TIERS_PARTICULIER->value => [ProfileDeclarant::TIERS_PARTICULIER->value, true];
        yield ProfileDeclarant::TIERS_PRO->value => [ProfileDeclarant::TIERS_PRO->value, true];
        yield 'error' => ['error', false, 'La valeur "{{ value }}" n\'est pas un profil déclarant ou groupe de profils valide.'];
        yield 'BAILEUR' => ['BAILEUR', false, 'La valeur "{{ value }}" n\'est pas un profil déclarant ou groupe de profils valide.'];
    }
}
