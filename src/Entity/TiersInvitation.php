<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\TiersInvitationStatus;
use App\Repository\TiersInvitationRepository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

#[ORM\Entity(repositoryClass: TiersInvitationRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_tiers_invitation_signalement_status',
    columns: ['signalement_id', 'status']
)]
#[ORM\UniqueConstraint(
    name: 'unique_tiers_invitation_token',
    columns: ['token']
)]
#[ORM\Index(
    name: 'idx_tiers_invitation_signalement',
    columns: ['signalement_id']
)]
class TiersInvitation implements EntityHistoryInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne()]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', unique: true)]
    private Signalement $signalement;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank(message: 'Veuillez saisir un nom de famille.')]
    private ?string $lastname = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank(message: 'Veuillez saisir un prénom.')]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir une adresse e-mail.')]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telephone = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(enumType: TiersInvitationStatus::class)]
    private TiersInvitationStatus $status;

    public function __construct()
    {
        $this->token = bin2hex(random_bytes(32));
        $this->status = TiersInvitationStatus::WAITING;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

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

    public function isWaiting(): bool
    {
        return TiersInvitationStatus::WAITING === $this->status;
    }

    public function isAccepted(): bool
    {
        return TiersInvitationStatus::ACCEPTED === $this->status;
    }

    public function isRefused(): bool
    {
        return TiersInvitationStatus::REFUSED === $this->status;
    }

    public function accept(): static
    {
        $this->status = TiersInvitationStatus::ACCEPTED;

        return $this;
    }

    public function refuse(): static
    {
        $this->status = TiersInvitationStatus::REFUSED;

        return $this;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
