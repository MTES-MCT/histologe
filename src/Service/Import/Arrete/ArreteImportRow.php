<?php

namespace App\Service\Import\Arrete;

use App\Entity\Enum\ArreteType;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

class ArreteImportRow
{
    public const int FIRST_LINE = 2;
    public const string DATE_FORMAT = 'd/m/Y';

    #[Assert\NotBlank]
    #[Assert\LessThanOrEqual('today')]
    #[Context([
        DateTimeNormalizer::FORMAT_KEY => self::DATE_FORMAT,
    ])]
    private ?\DateTimeImmutable $dateArrete = null;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [ArreteType::class, 'getLabelList'])]
    private ?string $classificationArrete = null;

    #[Assert\GreaterThan(
        propertyPath: 'dateArrete',
        message: 'La date de mainlevée doit être supérieure à la date d\'arrêté.'
    )]
    #[Assert\LessThanOrEqual('today')]
    #[Context([
        DateTimeNormalizer::FORMAT_KEY => self::DATE_FORMAT,
    ])]
    private ?\DateTimeImmutable $dateArreteMainLevee = null;

    private ?string $numeroVoie = null;

    #[Assert\NotBlank]
    private ?string $nomVoie = null;

    #[Assert\NotBlank]
    private ?string $codePostal = null;

    #[Assert\NotBlank]
    private ?string $commune = null;

    private ?string $denominationSyndic = null;

    #[Assert\NotBlank]
    private ?string $identifiantParcellaire = null;

    private ?bool $addressToValidate = false;

    public function getAddress(): ?string
    {
        return $this->numeroVoie.' '.$this->nomVoie.' '.$this->codePostal.' '.$this->commune;
    }

    public function getDateArrete(): ?\DateTimeImmutable
    {
        return $this->dateArrete;
    }

    public function setDateArrete(string|\DateTimeImmutable|null $dateArrete): self
    {
        if (is_string($dateArrete)) {
            if (empty($dateArrete)) {
                $this->dateArrete = null;

                return $this;
            }
            $this->dateArrete = ($dateArreteWithoutMinute = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $dateArrete))
                ? $dateArreteWithoutMinute->setTime(0, 0)
                : null;
        } elseif ($dateArrete instanceof \DateTimeImmutable) {
            $this->dateArrete = $dateArrete->setTime(0, 0);
        } else {
            $this->dateArrete = $dateArrete;
        }

        return $this;
    }

    public function getClassificationArrete(): ?string
    {
        return $this->classificationArrete;
    }

    public function setClassificationArrete(?string $classificationArrete): self
    {
        $this->classificationArrete = $classificationArrete;

        return $this;
    }

    public function getDateArreteMainLevee(): ?\DateTimeImmutable
    {
        return $this->dateArreteMainLevee;
    }

    public function setDateArreteMainLevee(string|\DateTimeImmutable|null $dateArreteMainLevee): self
    {
        if (is_string($dateArreteMainLevee)) {
            if (empty($dateArreteMainLevee)) {
                $this->dateArreteMainLevee = null;

                return $this;
            }
            $this->dateArreteMainLevee = ($dateArreteMainLeveeWithoutMinute = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $dateArreteMainLevee))
                ? $dateArreteMainLeveeWithoutMinute->setTime(0, 0)
                : null;
        } elseif ($dateArreteMainLevee instanceof \DateTimeImmutable) {
            $this->dateArreteMainLevee = $dateArreteMainLevee->setTime(0, 0);
        } else {
            $this->dateArreteMainLevee = $dateArreteMainLevee;
        }

        return $this;
    }

    public function getNumeroVoie(): ?string
    {
        return $this->numeroVoie;
    }

    public function setNumeroVoie(?string $numeroVoie): self
    {
        $this->numeroVoie = $numeroVoie;

        return $this;
    }

    public function getNomVoie(): ?string
    {
        return $this->nomVoie;
    }

    public function setNomVoie(?string $nomVoie): self
    {
        $this->nomVoie = $nomVoie;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getCommune(): ?string
    {
        return $this->commune;
    }

    public function setCommune(?string $commune): self
    {
        $this->commune = $commune;

        return $this;
    }

    public function getDenominationSyndic(): ?string
    {
        return $this->denominationSyndic;
    }

    public function setDenominationSyndic(?string $denominationSyndic): self
    {
        $this->denominationSyndic = $denominationSyndic;

        return $this;
    }

    public function getIdentifiantParcellaire(): ?string
    {
        return $this->identifiantParcellaire;
    }

    public function setIdentifiantParcellaire(?string $identifiantParcellaire): self
    {
        $this->identifiantParcellaire = $identifiantParcellaire;

        return $this;
    }

    public function getAddressToValidate(): ?bool
    {
        return $this->addressToValidate;
    }

    public function setAddressToValidate(?bool $addressToValidate): self
    {
        $this->addressToValidate = $addressToValidate;

        return $this;
    }
}
