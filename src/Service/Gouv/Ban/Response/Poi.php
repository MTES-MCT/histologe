<?php

namespace App\Service\Gouv\Ban\Response;

class Poi
{
    /** @var array<string> */
    private array $names = [];
    /** @var array<string> */
    private array $categories = [];
    /** @var array<string> */
    private array $postCodes = [];
    /** @var array<string> */
    private array $cityCodes = [];
    /** @var array<string, mixed> */
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

    /**
     * @return array<string>
     */
    public function getNames(): array
    {
        return $this->names;
    }

    /**
     * @return array<string>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @return array<string>
     */
    public function getPostCodes(): array
    {
        return $this->postCodes;
    }

    /**
     * @return array<string>
     */
    public function getCityCodes(): array
    {
        return $this->cityCodes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtrafields(): array
    {
        return $this->extrafields;
    }
}
