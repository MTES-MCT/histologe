<?php

namespace App\Serializer;

namespace App\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class SignalementDraftRequestSerializer extends Serializer
{
    public function __construct($normalizers)
    {
        parent::__construct($normalizers, [new JsonEncoder()]);
    }
}
