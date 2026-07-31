<?php

namespace App\Entity;

use App\Repository\InAppCommunicationUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: InAppCommunicationUserRepository::class)]
#[UniqueConstraint(name: 'user_in_app_communication_unique', columns: ['user_id', 'in_app_communication_id'])]
class InAppCommunicationUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inAppCommunicationUsers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(inversedBy: 'inAppCommunicationUsers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private InAppCommunication $inAppCommunication;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $seenAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getInAppCommunication(): InAppCommunication
    {
        return $this->inAppCommunication;
    }

    public function setInAppCommunication(InAppCommunication $inAppCommunication): static
    {
        $this->inAppCommunication = $inAppCommunication;

        return $this;
    }

    public function getSeenAt(): ?\DateTimeImmutable
    {
        return $this->seenAt;
    }

    public function setSeenAt(?\DateTimeImmutable $seenAt): static
    {
        $this->seenAt = $seenAt;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }
}
