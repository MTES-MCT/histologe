<?php

namespace App\Dto;

use App\Entity\Signalement;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ReponseInjonctionBailleur
{
    public const REPONSE_OUI = 1;
    public const REPONSE_OUI_AVEC_AIDE = 2;
    public const REPONSE_NON = 3;

    #[Assert\NotBlank()]
    private ?Signalement $signalement = null;

    #[Assert\NotBlank()]
    private ?int $reponse = null;

    private ?string $description = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        if (in_array($this->reponse, [self::REPONSE_OUI_AVEC_AIDE, self::REPONSE_NON]) && empty($this->description)) {
            $context->buildViolation('Veuillez renseigner un commentaire.')
                ->atPath('description')
                ->addViolation();
        }
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getReponse(): ?int
    {
        return $this->reponse;
    }

    public function setReponse(int $reponse): self
    {
        $this->reponse = $reponse;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
