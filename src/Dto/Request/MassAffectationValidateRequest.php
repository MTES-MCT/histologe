<?php

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Corps JSON de la route back_signalements_mass_affectation_validate.
 */
class MassAffectationValidateRequest
{
    #[Assert\NotBlank(message: 'Le jeton CSRF est manquant.')]
    public ?string $_token = null;

    #[Assert\NotBlank(message: 'Le partenaire est obligatoire.')]
    #[Assert\Positive(message: 'Le partenaire est invalide.')]
    public ?int $partnerId = null;

    /**
     * @var array<int>
     */
    #[Assert\NotBlank(message: 'Aucun signalement à affecter.')]
    #[Assert\All([new Assert\Type(type: 'integer', message: 'Les identifiants de signalements sont invalides.')])]
    public array $ids = [];

    public function getPartnerId(): int
    {
        return (int) $this->partnerId;
    }

    /**
     * @return array<int>
     */
    public function getIds(): array
    {
        return $this->ids;
    }
}
