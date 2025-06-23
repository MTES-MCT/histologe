<?php

namespace App\Dto\Api\Request;

/**
 * Interface used to provide descriptions with file links
 * (exemple : suivi description and visite description).
 */
interface RequestFileInterface
{
    /**
     * @return array<mixed>
     */
    public function getFiles(): array;

    public function getDescription(): ?string;
}
