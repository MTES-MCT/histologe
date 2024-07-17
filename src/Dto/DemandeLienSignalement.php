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

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getAdresseHelper(): string
    {
        return $this->adresseHelper;
    }

    public function setAdresseHelper(string $adresseHelper): self
    {
        $this->adresseHelper = $adresseHelper;

        return $this;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }
}
