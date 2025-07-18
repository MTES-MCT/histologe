<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Repository\ApiUserTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;

#[ORM\Entity(repositoryClass: ApiUserTokenRepository::class)]
class ApiUserToken implements EntityHistoryInterface
{
    public const string EXPIRATION_TIME = '+60 minutes';
    public const string CLEAN_EXPIRATION_PERIOD = '-1 day';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'apiUserTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $ownedBy = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $token = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expiresAt = null;

    /**
     * @throws RandomException
     */
    public function __construct()
    {
        $this->generate();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwnedBy(): ?User
    {
        return $this->ownedBy;
    }

    public function setOwnedBy(?User $ownedBy): static
    {
        $this->ownedBy = $ownedBy;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @throws RandomException
     */
    public function generate(): static
    {
        $this->expiresAt = new \DateTimeImmutable(self::EXPIRATION_TIME);
        $this->token = bin2hex(random_bytes(32));

        return $this;
    }

    public function isValid(): bool
    {
        return $this->getExpiresAt() > new \DateTimeImmutable();
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE];
    }
}
