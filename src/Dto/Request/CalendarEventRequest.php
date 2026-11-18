<?php

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CalendarEventRequest
{
    // Période la plus longue affichée par le calendrier : 6 semaines en vue mois
    public const int MAX_PERIOD_DAYS = 42;

    /**
     * @param array<string>|null $operateurs
     */
    public function __construct(
        private readonly ?string $territoire = null,
        #[Assert\All([new Assert\Type('string')])]
        private readonly ?array $operateurs = null,
        #[Assert\Date(message: 'La date de début n\'est pas une date valide')]
        private readonly ?string $dateDebut = null,
        #[Assert\Date(message: 'La date de fin n\'est pas une date valide')]
        private readonly ?string $dateFin = null,
        private readonly ?string $uniquementMesDossiers = null,
    ) {
    }

    #[Assert\Callback]
    public function validatePeriod(ExecutionContextInterface $context): void
    {
        if (null === $this->dateDebut || null === $this->dateFin) {
            return;
        }

        $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->dateDebut);
        $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->dateFin);
        if (false === $start || false === $end) {
            return;
        }

        if ($end <= $start || $start->diff($end)->days > self::MAX_PERIOD_DAYS) {
            $context->buildViolation('La période demandée n\'est pas valide')
                ->atPath('dateFin')
                ->addViolation();
        }
    }

    public function getTerritoire(): ?string
    {
        return $this->territoire;
    }

    /** @return array<string>|null */
    public function getOperateurs(): ?array
    {
        return $this->operateurs;
    }

    public function getDateDebut(): ?string
    {
        return $this->dateDebut;
    }

    public function getDateFin(): ?string
    {
        return $this->dateFin;
    }

    public function getUniquementMesDossiers(): ?string
    {
        return $this->uniquementMesDossiers;
    }
}
