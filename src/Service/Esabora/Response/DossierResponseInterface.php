<?php

namespace App\Service\Esabora\Response;

interface DossierResponseInterface
{
    public function getStatusCode(): ?int;

    public function getErrorReason(): ?string;

    public function getSasEtat(): ?string;

    public function getSasCauseRefus(): ?string;

    public function getEtat(): ?string;
}
