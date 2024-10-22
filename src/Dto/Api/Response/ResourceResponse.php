<?php

namespace App\Dto\Api\Response;

use Symfony\Component\Uid\Uuid;

class ResourceResponse
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $reference = null,
        public ?string $message = null,
        public ?int $status = null,
        public ?string $createdAt = null,
    ) {
        $this->reference = '2024-'.rand(1, 200);
        $this->uuid = Uuid::v4();
        $this->status = 1;
        $this->createdAt = (new \DateTimeImmutable())->format(\DATE_ATOM);
    }
}
