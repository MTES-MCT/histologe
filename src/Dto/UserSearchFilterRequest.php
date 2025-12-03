<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserSearchFilterRequest
{
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    public ?string $name = null;

    #[Assert\Type('array', message: 'Les paramètres doivent être un tableau.')]
    public ?array $params = null;

    #[Assert\NotBlank(message: 'Le jeton CSRF est manquant.')]
    public ?string $_token = null;
}
