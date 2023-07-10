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

    public function encode(?string $filename): ?string
    {
        if (null !== $filename) {
            $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;

            $type = pathinfo($bucketFilepath, \PATHINFO_EXTENSION);

            $data = @file_get_contents($bucketFilepath);
            if (false !== $data) {
                $data64 = base64_encode($data);

                return "data:image/$type;base64,$data64";
            }
        }

        return null;
    }
}
