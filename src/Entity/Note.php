<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\EntitySanitizerInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Repository\NoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_signalement', columns: ['user_id', 'signalement_id'])]
class Note implements EntityHistoryInterface, EntitySanitizerInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\ManyToOne(inversedBy: 'notes')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    private ?Signalement $signalement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(?Signalement $signalement): static
    {
        $this->signalement = $signalement;

        return $this;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    public function sanitize(HtmlSanitizerInterface $htmlSanitizer): void
    {
        if (!empty($this->content)) {
            $this->content = $htmlSanitizer->sanitize($this->content);
        }
    }

    public function getDescription(): ?string
    {
        return $this->content;
    }
}
