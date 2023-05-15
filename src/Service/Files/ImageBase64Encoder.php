<?php

namespace App\Service\Files;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\RuntimeExtensionInterface;

class ImageBase64Encoder implements RuntimeExtensionInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function encode(string $filename): string
    {
        $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;

        $type = pathinfo($bucketFilepath, \PATHINFO_EXTENSION);
        $data = base64_encode(file_get_contents($bucketFilepath));

        return "data:image/$type;base64,$data";
    }
}
