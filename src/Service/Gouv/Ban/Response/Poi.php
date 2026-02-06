<?php

namespace App\Service\Gouv\Ban\Response;

class Poi
{
    private array $names = [];
    private array $categories = [];
    private array $postCodes = [];
    private array $cityCodes = [];
    private array $extrafields = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(?array $data = null)
    {
        if (null !== $data && !empty($data['features'][0]['properties'])) {
            $properties = $data['features'][0]['properties'];
            $this->names = $properties['name'] ?? [];
            $this->categories = $properties['category'] ?? [];
            $this->postCodes = $properties['postcode'] ?? [];
            $this->cityCodes = $properties['citycode'] ?? [];
            $this->extrafields = $properties['extrafields'] ?? [];
        }
    }

    public function getNames(): array
    {
        return $this->names;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getPostCodes(): array
    {
        return $this->postCodes;
    }

    public function getCityCodes(): array
    {
        return $this->cityCodes;
    }

    public function getExtrafields(): array
    {
        return $this->extrafields;
    }
}
