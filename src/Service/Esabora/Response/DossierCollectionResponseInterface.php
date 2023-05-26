<?php

namespace App\Service\Esabora\Response;

interface DossierCollectionResponseInterface
{
    public function getSasEtat(): string;

    public function getStatusCode(): int;

    public function getErrorReason(): ?string;

    public function getCollection(): array;
}
