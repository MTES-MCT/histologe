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
        $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
        $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;
        file_put_contents($tmpFilepath, file_get_contents($bucketFilepath));

        $type = pathinfo($tmpFilepath, \PATHINFO_EXTENSION);
        $data = base64_encode(file_get_contents($tmpFilepath));

        return "data:image/$type;base64,$data";
    }
}
