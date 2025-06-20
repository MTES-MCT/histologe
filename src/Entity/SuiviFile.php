<?php

namespace App\Entity;

use App\Repository\SuiviFileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuiviFileRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_suivi_file', columns: ['suivi_id', 'file_id'])]
class SuiviFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'suiviFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Suivi $Suivi = null;

    #[ORM\ManyToOne]
    private ?File $File = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSuivi(): ?Suivi
    {
        return $this->Suivi;
    }

    public function setSuivi(?Suivi $Suivi): static
    {
        $this->Suivi = $Suivi;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->File;
    }

    public function setFile(?File $File): static
    {
        $this->File = $File;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
