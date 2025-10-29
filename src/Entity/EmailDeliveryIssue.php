<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\BrevoEvent;
use App\Repository\EmailDeliveryIssueRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: EmailDeliveryIssueRepository::class)]
#[ORM\HasLifecycleCallbacks()]
#[UniqueEntity('email')]
class EmailDeliveryIssue
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(enumType: BrevoEvent::class)]
    private ?BrevoEvent $event = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason;

    /** @var array<string, mixed> $payload */
    #[ORM\Column]
    private array $payload = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getEvent(): ?BrevoEvent
    {
        return $this->event;
    }

    public function setEvent(BrevoEvent $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }
}
