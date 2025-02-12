<?php

namespace App\Dto\Api\Request;

/**
 * Interface used to provide descriptions with file links
 * (exemple : suivi description and visite description).
 */
interface RequestFileInterface
{
    public function getFiles();

    public function getDescription();
}
