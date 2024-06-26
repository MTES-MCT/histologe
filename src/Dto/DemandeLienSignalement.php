<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DemandeLienSignalement
{
    #[Assert\NotBlank()]
    #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
    private string $email;

    #[Assert\NotBlank()]
    private string $adresseHelper;

    private ?string $adresse = null;

    private ?string $codePostal = null;

    private ?string $ville = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (empty(trim((string) $this->adresse)) || empty(trim((string) $this->codePostal)) || empty(trim((string) $this->ville))) {
            $context->buildViolation('Vous devez sélectionner une adresse dans la liste des propositions')
                ->atPath('adresseHelper')
                ->addViolation();
        }
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getAdresseHelper(): string
    {
        return $this->adresseHelper;
    }

    public function setAdresseHelper(string $adresseHelper): void
    {
        $this->adresseHelper = $adresseHelper;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): void
    {
        $this->adresse = $adresse;
    }

    public function getCodePostal(): string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): void
    {
        $this->codePostal = $codePostal;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): void
    {
        $this->ville = $ville;
    }
}
