<?php

namespace App\Service\DataGouv\Response;

class Address
{
    private ?string $label = null;
    private ?string $street = null;
    private ?string $zipCode = null;
    private ?string $city = null;
    private ?string $inseeCode = null;
    private ?string $longitude = null;
    private ?string $latitude = null;

    public function __construct(?array $data = null)
    {
        if (null !== $data && !empty($data['features'][0]['properties'])) {
            $properties = $data['features'][0]['properties'];
            $this->label = $properties['label'] ?? null;
            $this->street = $properties['name'] ?? null;
            $this->zipCode = $properties['postcode'] ?? null;
            $this->city = $properties['city'] ?? null;
            $this->inseeCode = $properties['citycode'] ?? null;
        }

        if (null !== $data && !empty($data['features'][0]['geometry']['coordinates'])) {
            $coordinates = $data['features'][0]['geometry']['coordinates'];
            $this->longitude = $coordinates[0] ?? null;
            $this->latitude = $coordinates[1] ?? null;
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

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getGeoloc(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}
