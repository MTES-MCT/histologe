<?php

namespace App\Service\Interconnection\Esabora\Response;

interface DossierCollectionResponseInterface
{
    public function getSasEtat(): string;

    public function getStatusCode(): int;

    public function getErrorReason(): ?string;

    /**
     * @return array<mixed>
     */
    public function getCollection(): array;
}
