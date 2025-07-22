<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\HistoryEntryEvent;
use App\Repository\PopNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PopNotificationRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class PopNotification implements EntityHistoryInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'popNotifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var array<mixed> $params */
    #[ORM\Column]
    private array $params = [];

    public function getId(): ?int
    {
        return $this->id;
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

    /** @return array<mixed> */
    public function getParams(): array
    {
        return $this->params;
    }

    /** @param array<mixed> $params */
    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
