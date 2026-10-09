<?php

namespace App\Dto\Request\Signalement;

use App\Validator\DateNaissanceValidatorTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class InformationsBailleurRequest implements RequestInterface
{
    use DateNaissanceValidatorTrait;

    public function __construct(
        #[Assert\Choice(choices: ['oui', 'non'], message: 'Le champ "bénéficiaire RSA" est incorrect.')]
        private readonly ?string $beneficiaireRsa = null,
        #[Assert\Choice(choices: ['oui', 'non'], message: 'Le champ "bénéficiaire FSL" est incorrect.')]
        private readonly ?string $beneficiaireFsl = null,
        #[Assert\Length(max: 50, maxMessage: 'Le revenu fiscal ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $revenuFiscal = null,
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $dateNaissance = null,
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $this->validateDateNaissance($this->dateNaissance, 'dateNaissance', $context);
    }

    public function getBeneficiaireRsa(): ?string
    {
        return $this->beneficiaireRsa;
    }

    public function getBeneficiaireFsl(): ?string
    {
        return $this->beneficiaireFsl;
    }

    public function getRevenuFiscal(): ?string
    {
        return $this->revenuFiscal;
    }

    public function getDateNaissance(): ?string
    {
        return $this->dateNaissance;
    }
}
