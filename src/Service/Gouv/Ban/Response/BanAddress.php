<?php

namespace App\Service\Gouv\Ban\Response;

class BanAddress
{
    private ?string $label = null;
    private ?string $housenumber = null;
    private ?string $street = null;
    private ?string $zipCode = null;
    private ?string $city = null;
    private ?string $inseeCode = null;
    private ?float $score = 0;
    private ?string $banId = null;
    private ?float $longitude = null;
    private ?float $latitude = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(?array $data = null)
    {
        if (null !== $data && !empty($data['features'][0]['properties'])) {
            $properties = $data['features'][0]['properties'];
            $this->label = $properties['label'] ?? null;
            $this->housenumber = $properties['housenumber'] ?? null;
            $this->street = $properties['street'] ?? null;
            $this->zipCode = $properties['postcode'] ?? null;
            $this->city = $properties['city'] ?? null;
            $this->score = $properties['score'] ?? null;
            $this->inseeCode = $properties['citycode'] ?? null;
            $this->banId = $properties['id'] ?? null;
        }

        if (null !== $data && !empty($data['features'][0]['geometry']['coordinates'])) {
            $coordinates = $data['features'][0]['geometry']['coordinates'];
            $this->longitude = isset($coordinates[0]) ? (float) $coordinates[0] : null;
            $this->latitude = isset($coordinates[1]) ? (float) $coordinates[1] : null;
        }
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getInseeCode(): ?string
    {
        return $this->inseeCode;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function getBanId(): ?string
    {
        return $this->banId;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getHousenumber(): ?string
    {
        return $this->housenumber;
    }
}
