<?php

namespace App\Service\Files;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\RuntimeExtensionInterface;

class ImageBase64Encoder implements RuntimeExtensionInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger,
    ) {
    }

    public function encode(?string $filename): ?string
    {
        if (null !== $filename) {
            $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;

            $type = pathinfo($bucketFilepath, \PATHINFO_EXTENSION);

            try {
                $data = file_get_contents($bucketFilepath);
                $data64 = base64_encode($data);

                return "data:image/$type;base64,$data64";
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return null;
    }
}
