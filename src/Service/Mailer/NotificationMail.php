<?php

namespace App\Service\Mailer;

use App\Entity\Territory;

class NotificationMail
{
    public function __construct(
        private readonly NotificationMailerType $type,
        private readonly array|string $to,
        private readonly array $params,
        private readonly ?Territory $territory
    ) {
    }

    public function getType(): NotificationMailerType
    {
        return $this->type;
    }

    public function getTo(): array|string
    {
        return $this->to;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function getEmails(): array
    {
        return \is_array($this->to) ? $this->to : [$this->to];
    }
}
