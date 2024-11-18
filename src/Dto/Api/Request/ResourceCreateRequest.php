<?php

namespace App\Dto\Api\Request;

class ResourceCreateRequest
{
    public function __construct(
        public string $reference,
        public string $message,
    ) {
    }
}
