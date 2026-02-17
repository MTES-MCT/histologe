<?php

namespace App\Dto\ServiceSecours;

use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep1
{
    #[Assert\NotBlank(message: 'Le matricule est obligatoire.', groups: ['step1'])]
    #[Assert\Length(max: 50, groups: ['step1'])]
    public ?string $matriculeDeclarant = null;

    #[Assert\Length(max: 50, groups: ['step1'])]
    public ?string $nomDeclarant = null;

    #[Assert\Length(max: 50, groups: ['step1'])]
    public ?string $origineMission = null;

    public ?\DateTimeImmutable $dateMission = null;

    #[Assert\Length(max: 50, groups: ['step1'])]
    public ?string $ordreMission = null;
}
